<?php

namespace App\Support\Onboarding;

use App\Enums\ExerciseType;
use App\Enums\ProgramStatus;
use App\Enums\RiskTier;
use App\Models\AssessmentResult;
use App\Models\Exercise;
use App\Models\Program;
use App\Models\ProgramEvaluation;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Builds, progresses and monitors exercise programs (Exercise Recommendation and Monitoring & Reassessment PRDs).
 * Server side only.
 */
final class Programs
{
    /**
     * Generates the self-exercise program of a stratified assessment. Refuses Risiko Tinggi (and pediatric):
     * this is the server-side safety block of acceptance criterion 2.
     */
    public static function build(AssessmentResult $assessment): Program
    {
        $tier = $assessment->tier;

        if (! $assessment->isStratified() || ! $tier || ! Intervention::canSelfExercise($tier, $assessment->domain)) {
            throw new DomainException('Latihan mandiri tidak diberikan untuk kategori risiko ini.');
        }

        if ($existing = $assessment->program()->first()) {
            return $existing;
        }

        $areas = Intervention::areas($assessment->screening?->regions ?? []);
        $slugs = Intervention::select(Intervention::exerciseTypes($tier, $assessment->domain, $areas), $areas, self::library());
        $pathway = Intervention::pathway($tier);

        return self::create($assessment, $slugs, [
            'title' => "Program {$pathway['label']} · {$assessment->domain->label()}",
            'pathway' => $pathway['key'],
            'needs_monitoring' => $tier === RiskTier::Moderate,
            'intensity_level' => 1,
            'evaluation_interval_weeks' => $pathway['weeks'],
        ]);
    }

    /** "Selesai (Maintenance Program)": general fitness exercises, monitored less often. */
    public static function buildMaintenance(Program $previous): Program
    {
        $assessment = $previous->assessment;
        $areas = Intervention::areas($assessment->screening?->regions ?? []);
        $types = [ExerciseType::Stretching, ExerciseType::Strengthening, ExerciseType::Aerobic];
        $slugs = Intervention::select($types, $areas, self::library());

        return self::create($assessment, $slugs, [
            'title' => Intervention::MAINTENANCE['label'].' · '.$assessment->domain->label(),
            'pathway' => Intervention::MAINTENANCE['key'],
            'needs_monitoring' => false,
            'intensity_level' => Intervention::MAINTENANCE['level'],
            'evaluation_interval_weeks' => Intervention::MAINTENANCE['weeks'],
        ], 'Tujuan fisioterapi tercapai');
    }

    /** Re-applies the dose after a level change. */
    public static function redose(Program $program): void
    {
        $tier = Intervention::doseTier($program->pathway);

        foreach ($program->items()->with('exercise')->get() as $item) {
            $item->update(Intervention::dose($tier, $item->exercise->dose_unit, $item->exercise->type->value, $program->intensity_level));
        }
    }

    /**
     * "Monitoring & Reassessment": compares the answers with the baseline assessment, decides, adjusts the program.
     *
     * @param array{pain: int, global_change: string, new_complaint: bool, side_effects: ?string, barriers: string[]} $general
     * @param array<string, array<string, mixed>> $answers instrument key => validated answers
     * @clinical P-MO-19
     */
    public static function monitor(Program $program, array $general, array $answers): ProgramEvaluation
    {
        $assessment = $program->assessment;
        $instruments = $assessment->instruments ?? [];
        $answers = array_intersect_key($answers, array_flip($instruments));

        $comparison = Monitoring::compare($instruments, $assessment->answers ?? [], $answers, (int) $assessment->pain, $general['pain']);
        $risk = RiskStratification::stratify($instruments, $answers, $general['pain']);

        $lastImprovement = $program->evaluations()->where('improved', true)->latest('id')->first()?->created_at
            ?? $program->starts_on ?? $program->created_at;
        $weeks = (int) floor($lastImprovement->diffInDays(now()) / 7);

        $decision = Monitoring::decide(
            $comparison, $general['pain'], $general['global_change'], $general['new_complaint'], filled($general['side_effects'] ?? null),
            $risk['tier'], $risk['emergencies'] !== [], $weeks, $program->isMaintenance(),
        );

        $from = $program->evaluations()->latest('id')->first()?->created_at ?? $program->starts_on ?? $program->created_at;
        $adherence = Intervention::adherence($program->logs()->pluck('performed_at')->all(), $from, now());

        return DB::transaction(function () use ($program, $assessment, $general, $answers, $comparison, $risk, $decision, $adherence) {
            $evaluation = $program->evaluations()->create([
                'clinic_id' => $program->clinic_id,
                'patient_id' => $program->patient_id,
                'pain' => $general['pain'],
                'global_change' => $general['global_change'],
                'new_complaint' => $general['new_complaint'],
                'side_effects' => filled($general['side_effects'] ?? null) ? $general['side_effects'] : null,
                'barriers' => array_values($general['barriers'] ?? []),
                'answers' => $answers,
                'comparison' => $comparison,
                'tier' => $risk['tier'],
                'adherence' => $adherence,
                'improved' => $decision['significant'],
                'goal_achieved' => $decision['goal_achieved'],
                'outcome' => $decision['outcome'],
            ]);

            $next = today()->addWeeks($program->evaluation_interval_weeks);

            switch ($decision['outcome']) {
                case Monitoring::OUTCOME_PROGRESS:                  // intensity + 1 and a more challenging variation
                    $level = min(Intervention::MAX_LEVEL, $program->intensity_level + 1);
                    $program->update(['intensity_level' => $level, 'next_evaluation_on' => $next]);
                    self::redose($program);
                    self::addVariation($program);
                    break;

                case Monitoring::OUTCOME_MODIFY:                    // adherence first; otherwise a lighter dose
                    $program->update([
                        'intensity_level' => $adherence < Monitoring::LOW_ADHERENCE ? $program->intensity_level : max(1, $program->intensity_level - 1),
                        'next_evaluation_on' => $next,
                    ]);
                    self::redose($program);
                    break;

                case Monitoring::OUTCOME_MAINTENANCE:
                    self::buildMaintenance($program);
                    break;

                default:                                            // reassess / referral: paused until reassessment
                    $program->update(['status' => ProgramStatus::NeedsReview]);
                    if ($risk['emergencies'] !== []) {
                        $assessment->escalate($risk['emergencies']);
                    }
            }

            return $evaluation;
        });
    }

    /** "Variasi latihan lebih menantang": one exercise of the program's types that is not in it yet. · @clinical P-MO-20 */
    private static function addVariation(Program $program): void
    {
        $program->load('items.exercise');
        $have = $program->items->pluck('exercise.slug')->all();
        $types = $program->items->pluck('exercise.type')->unique()->map->value->all();

        foreach (ExerciseLibrary::ITEMS as $slug => [, $type]) {
            if (in_array($type, $types, true) && ! in_array($slug, $have, true)) {
                $exercise = Exercise::withoutGlobalScopes()->where('slug', $slug)->first();
                $program->items()->create(
                    ['exercise_id' => $exercise->id, 'sort_order' => $program->items->count()]
                    + Intervention::dose(Intervention::doseTier($program->pathway), $exercise->dose_unit, $type, $program->intensity_level),
                );

                return;
            }
        }
    }

    /** @return array<int, array{slug: string, type: string, body_area: string}> */
    private static function library(): array
    {
        if (! ExerciseLibrary::isSynced()) {
            ExerciseLibrary::sync();
        }

        $library = [];
        foreach (ExerciseLibrary::ITEMS as $slug => [, $type, $area]) {
            $library[] = ['slug' => $slug, 'type' => $type, 'body_area' => $area];
        }

        return $library;
    }

    /** @param string[] $slugs */
    private static function create(AssessmentResult $assessment, array $slugs, array $attributes, string $endReason = 'Diganti program baru'): Program
    {
        $exercises = Exercise::withoutGlobalScopes()->whereNull('clinic_id')->whereIn('slug', $slugs)->get()->keyBy('slug');
        $doseTier = Intervention::doseTier($attributes['pathway']);

        return DB::transaction(function () use ($assessment, $slugs, $exercises, $attributes, $endReason, $doseTier) {
            Program::query()->where('patient_id', $assessment->patient_id)->current()->get()
                ->each(fn (Program $p) => $p->end($endReason));

            $program = Program::create($attributes + [
                'clinic_id' => $assessment->clinic_id,
                'patient_id' => $assessment->patient_id,
                'created_by' => null,
                'assessment_id' => $assessment->id,
                'status' => ProgramStatus::Active,
                'starts_on' => today(),
                'next_evaluation_on' => today()->addWeeks($attributes['evaluation_interval_weeks']),
            ]);

            foreach ($slugs as $i => $slug) {
                $exercise = $exercises[$slug];
                $program->items()->create(
                    ['exercise_id' => $exercise->id, 'sort_order' => $i]
                    + Intervention::dose($doseTier, $exercise->dose_unit, $exercise->type->value, $attributes['intensity_level']),
                );
            }

            return $program;
        });
    }
}

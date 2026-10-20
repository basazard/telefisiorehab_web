<?php

namespace App\Support\Onboarding;

use App\Enums\ProgramStatus;
use App\Enums\RiskTier;
use App\Models\AssessmentResult;
use App\Models\Exercise;
use App\Models\Program;
use App\Models\ProgramEvaluation;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Builds, progresses and evaluates exercise programs (Exercise / Telefisioterapi Recommendation PRD). Server side only. */
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

        if ($existing = Program::query()->where('assessment_id', $assessment->id)->first()) {
            return $existing;
        }

        if (! ExerciseLibrary::isSynced()) {
            ExerciseLibrary::sync();
        }

        $library = [];
        foreach (ExerciseLibrary::ITEMS as $slug => [, $type, $area]) {
            $library[] = ['slug' => $slug, 'type' => $type, 'body_area' => $area];
        }

        $areas = Intervention::areas($assessment->screening?->regions ?? []);
        $slugs = Intervention::select(Intervention::exerciseTypes($tier, $assessment->domain, $areas), $areas, $library);
        $exercises = Exercise::withoutGlobalScopes()->whereNull('clinic_id')->whereIn('slug', $slugs)->get()->keyBy('slug');
        $pathway = Intervention::pathway($tier);

        return DB::transaction(function () use ($assessment, $tier, $slugs, $exercises, $pathway) {
            Program::query()->where('patient_id', $assessment->patient_id)->current()->get()
                ->each(fn (Program $p) => $p->end('Diganti program baru'));

            $program = Program::create([
                'clinic_id' => $assessment->clinic_id,
                'patient_id' => $assessment->patient_id,
                'created_by' => null,
                'assessment_id' => $assessment->id,
                'title' => "Program {$pathway['label']} · {$assessment->domain->label()}",
                'status' => ProgramStatus::Active,
                'pathway' => $pathway['key'],
                'needs_monitoring' => $tier === RiskTier::Moderate,
                'intensity_level' => 1,
                'evaluation_interval_weeks' => $pathway['weeks'],
                'starts_on' => today(),
                'next_evaluation_on' => today()->addWeeks($pathway['weeks']),
            ]);

            foreach ($slugs as $i => $slug) {
                $exercise = $exercises[$slug];
                $program->items()->create(
                    ['exercise_id' => $exercise->id, 'sort_order' => $i]
                    + Intervention::dose($tier, $exercise->dose_unit, $exercise->type->value, 1),
                );
            }

            return $program;
        });
    }

    /** Re-applies the dose after a level change. */
    public static function redose(Program $program): void
    {
        $tier = $program->assessment->tier;

        foreach ($program->items()->with('exercise')->get() as $item) {
            $item->update(Intervention::dose($tier, $item->exercise->dose_unit, $item->exercise->type->value, $program->intensity_level));
        }
    }

    /**
     * "Monitoring dan Evaluasi": stores the questionnaire, decides "Apakah ada perbaikan?" and adjusts the program.
     * Ya: keep going, intensity + 1, next evaluation. Tidak: program paused for reassessment / re-consultation.
     *
     * @param array{pain: int, function: int, mobility: int, global_change: string, new_complaint: bool} $answers
     */
    public static function evaluate(Program $program, array $answers): ProgramEvaluation
    {
        $previous = $program->evaluations()->latest('id')->first();
        $from = $previous?->created_at ?? $program->starts_on ?? $program->created_at;
        $before = $previous
            ? ['pain' => $previous->pain, 'function' => $previous->function, 'mobility' => $previous->mobility]
            : ['pain' => (int) $program->assessment?->pain];

        $improved = Intervention::improved($answers, $before);
        $adherence = Intervention::adherence($program->logs()->pluck('performed_at')->all(), $from, now());

        return DB::transaction(function () use ($program, $answers, $improved, $adherence) {
            $evaluation = $program->evaluations()->create($answers + [
                'clinic_id' => $program->clinic_id,
                'patient_id' => $program->patient_id,
                'adherence' => $adherence,
                'improved' => $improved,
            ]);

            if ($improved) {
                $program->update([
                    'intensity_level' => min(Intervention::MAX_LEVEL, $program->intensity_level + 1),
                    'next_evaluation_on' => today()->addWeeks($program->evaluation_interval_weeks),
                ]);
                self::redose($program);
            } else {
                $program->update(['status' => ProgramStatus::NeedsReview]);
            }

            return $evaluation;
        });
    }
}

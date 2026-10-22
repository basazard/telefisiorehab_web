<?php

namespace Database\Seeders;

use App\Enums\ProgramStatus;
use App\Models\ExerciseLog;
use App\Models\Patient;
use App\Models\Program;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\Monitoring;
use App\Support\Onboarding\Programs;
use Illuminate\Database\Seeder;

/**
 * Demo programs, exercise logs (last 28 days) and monitoring entries (part of DemoSeeder). Programs are generated
 * by Programs::build() from the seeded assessment, then moved back in time.
 */
class ProgramSeeder extends Seeder
{
    private const LOG_DAYS = 28;

    /**
     * scenario => started (days ago), log pattern, pain [first, latest], last log (days ago),
     * evaluation [days ago, global change] or null, next evaluation (days from today), status
     */
    public const PLANS = [
        'rendah' => ['start' => 20, 'logs' => 'daily', 'pain' => [4, 2], 'until' => 1, 'eval' => null, 'next' => 8],
        'sedang_improving' => ['start' => 21, 'logs' => 'most', 'pain' => [5, 3], 'until' => 1, 'eval' => [7, 'better'], 'next' => 7],
        'sedang_low' => ['start' => 21, 'logs' => 'sparse', 'pain' => [5, 5], 'until' => 2, 'eval' => [6, 'same'], 'next' => 8],
        'sedang_overdue' => ['start' => 30, 'logs' => 'most', 'pain' => [6, 4], 'until' => 1, 'eval' => null, 'next' => -3],
        'needs_review' => ['start' => 35, 'logs' => 'most', 'pain' => [5, 7], 'until' => 4, 'eval' => [4, 'worse'], 'next' => null,
            'status' => ProgramStatus::NeedsReview],
        'geriatric' => ['start' => 14, 'logs' => 'most', 'pain' => [4, 3], 'until' => 1, 'eval' => null, 'next' => 14],
        'goal' => ['start' => 60, 'logs' => 'most', 'pain' => [5, 1], 'until' => 11, 'eval' => [10, 'goal'], 'next' => null],
    ];

    public function run(): void
    {
        foreach (PatientSeeder::CAST as [$email, , , , , , $scenario]) {
            if (! isset(self::PLANS[$scenario])) {
                continue;
            }

            $patient = Patient::query()->withoutGlobalScopes()->whereRelation('user', 'email', $email)->first();

            if (! $patient || $patient->programs()->exists()) {
                continue;
            }

            $this->plan($patient, $scenario, self::PLANS[$scenario]);
        }
    }

    private function plan(Patient $patient, string $scenario, array $plan): void
    {
        $assessment = $patient->assessments()->whereNotNull('stratified_at')->latest('id')->firstOrFail();
        $program = Programs::build($assessment);
        $this->moveBack($program, $plan['start'], $plan['next']);

        if (isset($plan['status'])) {
            $program->update(['status' => $plan['status']]);
        }

        $this->logs($patient, $program, $plan['logs'], $plan['start'], $plan['until'], $plan['pain']);

        if ($plan['eval']) {
            [$daysAgo, $change] = $plan['eval'];
            $this->evaluation($patient, $program, $daysAgo, $change, $plan['pain'][1]);
        }

        if ($scenario === 'goal') {
            // goal achieved 10 days ago: the program ended and a maintenance program followed
            $endedAt = now()->subDays(10);
            $program->forceFill(['status' => ProgramStatus::Ended, 'ended_at' => $endedAt, 'end_reason' => 'Tujuan fisioterapi tercapai'])->save();

            $maintenance = Programs::buildMaintenance($program);
            $this->moveBack($maintenance, 10, 18);
            $this->logs($patient, $maintenance, 'every_other', 10, 1, [1, 1]);
        }
    }

    private function moveBack(Program $program, int $startedDaysAgo, ?int $nextEvaluationInDays): void
    {
        $start = now()->subDays($startedDaysAgo)->setTime(9, 0);

        $program->forceFill([
            'starts_on' => $start->toDateString(),
            'next_evaluation_on' => $nextEvaluationInDays === null ? null : today()->addDays($nextEvaluationInDays)->toDateString(),
            'created_at' => $start,
            'updated_at' => $start,
        ])->save();
    }

    /** One log per exercise on each logged day, pain moving linearly from $pain[0] to $pain[1]. */
    private function logs(Patient $patient, Program $program, string $pattern, int $startedDaysAgo, int $untilDaysAgo, array $pain): void
    {
        $items = $program->items()->get();
        $first = min($startedDaysAgo - 1, self::LOG_DAYS);
        $span = max(1, $first - $untilDaysAgo);

        for ($daysAgo = $first; $daysAgo >= $untilDaysAgo; $daysAgo--) {
            $logged = match ($pattern) {
                'daily' => true,
                'most' => $daysAgo % 3 !== 0,           // about 2 of 3 days
                'every_other' => $daysAgo % 2 === 1,
                'sparse' => $daysAgo % 5 === 2,         // about 1 in 5 days: adherence below 50 %
            };

            if (! $logged) {
                continue;
            }

            $score = (int) round($pain[0] + ($pain[1] - $pain[0]) * ($first - $daysAgo) / $span);

            foreach ($items as $i => $item) {
                ExerciseLog::query()->forceCreate([
                    'clinic_id' => $patient->clinic_id,
                    'patient_id' => $patient->id,
                    'program_exercise_id' => $item->id,
                    'performed_at' => now()->subDays($daysAgo)->setTime(7, 30)->addMinutes(6 * $i),
                    'sets_done' => $item->sets,
                    'reps_done' => $item->reps,
                    'duration_seconds' => $item->minutes ? $item->minutes * 60 : null,
                    'pain_score' => $score,
                    'effort' => 5,
                ]);
            }
        }
    }

    private function evaluation(Patient $patient, Program $program, int $daysAgo, string $change, int $pain): void
    {
        $at = now()->subDays($daysAgo)->setTime(19, 0);
        $goal = $change === 'goal';

        ProgramEvaluation::query()->forceCreate([
            'clinic_id' => $patient->clinic_id,
            'patient_id' => $patient->id,
            'program_id' => $program->id,
            'pain' => $pain,
            'global_change' => $goal ? 'better' : $change,
            'new_complaint' => false,
            'adherence' => ['better' => 70, 'same' => 30, 'worse' => 60, 'goal' => 75][$change],
            'improved' => $goal || $change === 'better',
            'answers' => [],
            'comparison' => [],
            'tier' => $goal ? 'low' : null,
            'barriers' => $change === 'same' ? ['motivation', 'time'] : [],
            'outcome' => match ($change) {
                'better' => Monitoring::OUTCOME_PROGRESS,
                'same' => Monitoring::OUTCOME_MODIFY,
                'worse' => Monitoring::OUTCOME_REASSESS,
                'goal' => Monitoring::OUTCOME_MAINTENANCE,
            },
            'goal_achieved' => $goal,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}

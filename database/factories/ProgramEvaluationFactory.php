<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\Monitoring;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A monitoring entry. Improving by default; same() and worse() for the other answers.
 *
 * @extends Factory<ProgramEvaluation>
 */
class ProgramEvaluationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'patient_id' => fn (array $a) => Program::withoutGlobalScopes()->find($a['program_id'])->patient_id,
            'clinic_id' => fn (array $a) => Program::withoutGlobalScopes()->find($a['program_id'])->clinic_id,
            'pain' => 3,
            'global_change' => 'better',
            'new_complaint' => false,
            'adherence' => 80,
            'improved' => true,
            'answers' => [],
            'comparison' => [],
            'barriers' => [],
            'outcome' => Monitoring::OUTCOME_PROGRESS,
            'goal_achieved' => false,
        ];
    }

    public function same(): static
    {
        return $this->state(fn () => ['global_change' => 'same', 'improved' => false, 'outcome' => Monitoring::OUTCOME_MODIFY]);
    }

    public function worse(): static
    {
        return $this->state(fn () => ['global_change' => 'worse', 'improved' => false, 'outcome' => Monitoring::OUTCOME_REASSESS]);
    }

    public function goalAchieved(): static
    {
        return $this->state(fn () => ['pain' => 1, 'outcome' => Monitoring::OUTCOME_MAINTENANCE, 'goal_achieved' => true]);
    }
}

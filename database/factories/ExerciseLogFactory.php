<?php

namespace Database\Factories;

use App\Models\ExerciseLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExerciseLog>
 */
class ExerciseLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_exercise_id' => \App\Models\ProgramExercise::factory(),
            'patient_id' => fn (array $a) => \App\Models\ProgramExercise::find($a['program_exercise_id'])->program()->withoutGlobalScopes()->first()->patient_id,
            'clinic_id' => fn (array $a) => \App\Models\ProgramExercise::find($a['program_exercise_id'])->program()->withoutGlobalScopes()->first()->clinic_id,
            'performed_at' => now(),
            'sets_done' => 2,
            'reps_done' => 10,
            'pain_score' => 3,
            'effort' => 5,
        ];
    }
}

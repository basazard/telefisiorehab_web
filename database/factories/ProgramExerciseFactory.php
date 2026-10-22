<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\Program;
use App\Models\ProgramExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProgramExercise> */
class ProgramExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'exercise_id' => Exercise::factory(),
            'sets' => 2,
            'reps' => 10,
            'hold_seconds' => null,
            'sort_order' => 0,
        ];
    }
}

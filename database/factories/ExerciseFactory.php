<?php

namespace Database\Factories;

use App\Models\Exercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clinic_id' => null,
            'name' => fake()->unique()->words(3, true),
            'body_area' => 'knee',
            'type' => 'strengthening',
            'dose_unit' => 'reps',
            'instructions' => 'Lakukan perlahan dan terkontrol.',
        ];
    }
}

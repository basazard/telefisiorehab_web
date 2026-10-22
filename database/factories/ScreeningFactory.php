<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Screening;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Screening> */
class ScreeningFactory extends Factory
{
    /** In progress by default; completed() finishes it without a red flag. */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'clinic_id' => fn (array $attributes) => Patient::withoutGlobalScopes()->find($attributes['patient_id'])?->clinic_id,
            'main_complaints' => ['keys' => ['pain'], 'other' => null],
            'regions' => ['Left Knee'],
            'non_specific_location' => false,
            'pain_levels' => ['Left Knee' => 4],
            'max_pain' => 4,
            'characteristics' => [],
            'status' => 'in_progress',
            'current_step' => 1,
        ];
    }

    /** @param string[] $regions */
    public function regions(array $regions, int $pain = 4): static
    {
        return $this->state(fn () => ['regions' => $regions, 'pain_levels' => array_fill_keys($regions, $pain), 'max_pain' => $pain]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed', 'current_step' => 5, 'completed_at' => now()]);
    }

    /** Stopped by a red flag (pair it with a RedFlagCheckFactory row). */
    public function stopped(): static
    {
        return $this->state(fn () => ['status' => 'stopped', 'current_step' => 4]);
    }
}

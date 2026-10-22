<?php

namespace Database\Factories;

use App\Models\RedFlagCheck;
use App\Models\Screening;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RedFlagCheck> */
class RedFlagCheckFactory extends Factory
{
    /** Cleared (nothing flagged) by default; stopped() locks the patient. */
    public function definition(): array
    {
        return [
            'screening_id' => Screening::factory()->completed(),
            'patient_id' => fn (array $a) => Screening::withoutGlobalScopes()->find($a['screening_id'])->patient_id,
            'clinic_id' => fn (array $a) => Screening::withoutGlobalScopes()->find($a['screening_id'])->clinic_id,
            'answers' => [],
            'flagged' => [],
            'cleared' => true,
        ];
    }

    /** @param string[] $flagged */
    public function stopped(array $flagged = ['chest_pain']): static
    {
        return $this->state(fn () => [
            'screening_id' => Screening::factory()->stopped(),
            'answers' => array_fill_keys($flagged, 'yes'),
            'flagged' => $flagged,
            'cleared' => false,
            'stopped_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\ProgramStatus;
use App\Models\Patient;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An active program without items. Real programs come from Programs::build(); this is for tests that only need
 * the program row (status, pathway, dates).
 *
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'clinic_id' => fn (array $a) => Patient::withoutGlobalScopes()->find($a['patient_id'])?->clinic_id,
            'created_by' => null,
            'title' => 'Program latihan',
            'status' => ProgramStatus::Active,
            'pathway' => 'self_management',
            'needs_monitoring' => false,
            'intensity_level' => 1,
            'evaluation_interval_weeks' => 4,
            'starts_on' => today()->subDays(14),
            'next_evaluation_on' => today()->addDays(14),
        ];
    }

    public function guided(): static
    {
        return $this->state(fn () => ['pathway' => 'guided', 'needs_monitoring' => true, 'evaluation_interval_weeks' => 2]);
    }

    public function needsReview(): static
    {
        return $this->state(fn () => ['status' => ProgramStatus::NeedsReview]);
    }

    public function ended(string $reason = 'Diganti program baru'): static
    {
        return $this->state(fn () => ['status' => ProgramStatus::Ended, 'ended_at' => now(), 'end_reason' => $reason]);
    }
}

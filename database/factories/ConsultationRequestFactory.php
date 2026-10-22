<?php

namespace Database\Factories;

use App\Models\ConsultationRequest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ConsultationRequest> */
class ConsultationRequestFactory extends Factory
{
    /** An open telekonsultasi request; referral() and handled() for the other states. */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'clinic_id' => fn (array $a) => Patient::withoutGlobalScopes()->find($a['patient_id'])?->clinic_id,
            'assessment_id' => null,
            'type' => 'teleconsultation',
            'reason' => null,
            'status' => 'open',
        ];
    }

    public function referral(): static
    {
        return $this->state(fn () => ['type' => 'referral']);
    }

    public function handled(?int $by = null, string $note = 'Pasien sudah dihubungi dan dijadwalkan.'): static
    {
        return $this->state(fn () => ['status' => 'handled', 'handled_by' => $by, 'handled_at' => now(), 'staff_note' => $note]);
    }
}

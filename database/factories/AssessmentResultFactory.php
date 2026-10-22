<?php

namespace Database\Factories;

use App\Enums\RiskTier;
use App\Models\AssessmentResult;
use App\Models\Screening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A Clinical Assessment. In progress by default; low() / moderate() / high() complete and stratify it.
 *
 * @extends Factory<AssessmentResult>
 */
class AssessmentResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'screening_id' => Screening::factory()->completed(),
            'patient_id' => fn (array $a) => Screening::withoutGlobalScopes()->find($a['screening_id'])->patient_id,
            'clinic_id' => fn (array $a) => Screening::withoutGlobalScopes()->find($a['screening_id'])->clinic_id,
            'answers' => [],
            'pain' => 4,
            'domain' => 'musculoskeletal',
            'instruments' => [],
            'status' => 'in_progress',
            'current_step' => 1,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed', 'completed_at' => now()]);
    }

    public function stratified(RiskTier $tier): static
    {
        return $this->completed()->state(fn () => [
            'tier' => $tier->value, 'has_limitation' => $tier !== RiskTier::Low, 'outcome' => 'risk_classification',
            'risk_details' => [], 'stratified_at' => now(),
        ]);
    }

    public function low(): static
    {
        return $this->stratified(RiskTier::Low);
    }

    public function moderate(): static
    {
        return $this->stratified(RiskTier::Moderate);
    }

    public function high(): static
    {
        return $this->stratified(RiskTier::High);
    }
}

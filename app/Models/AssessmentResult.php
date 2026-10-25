<?php

namespace App\Models;

use App\Enums\PhysioDomain;
use App\Enums\RiskTier;
use App\Models\Concerns\BelongsToClinic;
use App\Models\Concerns\RecordsClinicalVersion;
use App\Support\Onboarding\RiskStratification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Table: assessments. (Named AssessmentResult to avoid clashing with the Support\Onboarding\Assessment scorer.) */
class AssessmentResult extends Model
{
    use HasFactory, BelongsToClinic, RecordsClinicalVersion;

    protected $table = 'assessments';

    protected $fillable = [
        'clinic_id', 'screening_id', 'patient_id', 'answers', 'lefs', 'odi', 'odi_percent',
        'rom', 'pain', 'tier', 'domain', 'domain_reason', 'track', 'instruments', 'scores', 'findings', 'warnings',
        'has_limitation', 'outcome', 'status', 'current_step', 'completed_at', 'risk_details', 'stratified_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'tier' => RiskTier::class,
            'domain' => PhysioDomain::class,
            'instruments' => 'array',
            'scores' => 'array',
            'findings' => 'array',
            'warnings' => 'array',
            'has_limitation' => 'boolean',
            'completed_at' => 'datetime',
            'risk_details' => 'array',
            'stratified_at' => 'datetime',
        ];
    }

    public function screening(): BelongsTo
    {
        return $this->belongsTo(Screening::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** The program recommended from this assessment (not the maintenance program that may follow it). */
    public function program(): HasOne
    {
        return $this->hasOne(Program::class, 'assessment_id')->where('pathway', '!=', Program::MAINTENANCE);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isStratified(): bool
    {
        return $this->stratified_at !== null;
    }

    /** Risiko Tinggi: the program continues, but only under physiotherapist supervision (for Rekomendasi Intervensi). */
    public function needsSupervision(): bool
    {
        return $this->tier === RiskTier::High;
    }

    /**
     * Scoring & Risk Stratification: stores the category and turns an emergency into a red-flag stop
     * (the patient sees the stop page; only clinic staff can unlock). Run once, on the server, on a completed assessment.
     */
    public function stratify(): void
    {
        $result = RiskStratification::stratify($this->instruments ?? [], $this->answers ?? [], (int) $this->pain);

        $this->update([
            'tier' => $result['tier'],
            'risk_details' => $result['items'],
            'stratified_at' => now(),
        ]);

        // A reassessment that ends in Risiko Tinggi stops any self-exercise program still running.
        if ($result['tier'] === RiskTier::High) {
            Program::query()->where('patient_id', $this->patient_id)->current()->get()
                ->each(fn (Program $p) => $p->end('Reassessment: risiko tinggi'));
        }

        if ($result['emergencies'] !== []) {
            $this->escalate($result['emergencies']);
        }
    }

    /**
     * "Eskalasi Gawat Darurat": records the emergency on the screening's red-flag check as a stop.
     * Used by Risk Stratification and by Monitoring.
     *
     * @param string[] $emergencies RedFlags::AUTO keys
     */
    public function escalate(array $emergencies): void
    {
        $check = RedFlagCheck::firstOrNew(['screening_id' => $this->screening_id]);
        $check->fill([
            'clinic_id' => $this->clinic_id,
            'patient_id' => $this->patient_id,
            'answers' => $check->answers ?? [],
            'flagged' => array_values(array_unique([...($check->flagged ?? []), ...$emergencies])),
            'cleared' => false,
            'stopped_at' => now(),
            'unlocked_at' => null,
            'unlocked_by' => null,
            'unlock_note' => null,
        ])->save();
    }
}

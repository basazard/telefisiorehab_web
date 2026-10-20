<?php

namespace App\Models;

use App\Enums\PhysioDomain;
use App\Enums\RiskTier;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Table: assessments. (Named AssessmentResult to avoid clashing with the Support\Onboarding\Assessment scorer.) */
class AssessmentResult extends Model
{
    use BelongsToClinic;

    protected $table = 'assessments';

    protected $fillable = [
        'clinic_id', 'screening_id', 'patient_id', 'answers', 'lefs', 'odi', 'odi_percent',
        'rom', 'pain', 'tier', 'domain', 'domain_reason', 'track', 'instruments', 'scores', 'findings', 'warnings',
        'has_limitation', 'outcome', 'status', 'current_step', 'completed_at',
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

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}

<?php

namespace App\Models;

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
        'rom', 'pain', 'tier', 'domain', 'track', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'tier' => RiskTier::class,
            'completed_at' => 'datetime',
        ];
    }

    public function screening(): BelongsTo
    {
        return $this->belongsTo(Screening::class);
    }
}

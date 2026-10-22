<?php

namespace App\Models;

use App\Enums\RiskTier;
use App\Models\Concerns\BelongsToClinic;
use App\Support\Onboarding\Monitoring;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One "Monitoring & Reassessment" entry of a program. `improved` = significant improvement vs the baseline. */
class ProgramEvaluation extends Model
{
    use HasFactory, BelongsToClinic;

    public const GLOBAL_CHANGE = ['better' => 'Lebih baik', 'same' => 'Sama saja', 'worse' => 'Lebih buruk'];

    protected $fillable = [
        'clinic_id', 'patient_id', 'program_id', 'pain', 'function', 'mobility', 'global_change', 'new_complaint', 'adherence', 'improved',
        'answers', 'comparison', 'tier', 'side_effects', 'barriers', 'outcome', 'goal_achieved',
    ];

    protected function casts(): array
    {
        return [
            'new_complaint' => 'boolean',
            'improved' => 'boolean',
            'answers' => 'array',
            'comparison' => 'array',
            'tier' => RiskTier::class,
            'barriers' => 'array',
            'goal_achieved' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function outcomeLabel(): string
    {
        return Monitoring::OUTCOMES[$this->outcome] ?? ($this->improved ? 'Ada perbaikan' : 'Belum ada perbaikan');
    }
}

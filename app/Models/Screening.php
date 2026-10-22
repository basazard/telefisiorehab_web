<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Screening extends Model
{
    use HasFactory, BelongsToClinic;

    protected $fillable = [
        'clinic_id', 'patient_id', 'main_complaints', 'regions', 'non_specific_location', 'pain_levels', 'max_pain',
        'characteristics', 'special_groups', 'status', 'current_step', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'main_complaints' => 'array',
            'regions' => 'array',
            'non_specific_location' => 'boolean',
            'pain_levels' => 'array',
            'characteristics' => 'array',
            'special_groups' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function redFlagCheck(): HasOne
    {
        return $this->hasOne(RedFlagCheck::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(AssessmentResult::class);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isStopped(): bool
    {
        return $this->status === 'stopped';
    }
}

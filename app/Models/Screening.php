<?php

namespace App\Models;

use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Screening extends Model
{
    use BelongsToClinic;

    protected $fillable = ['clinic_id', 'patient_id', 'regions', 'complaints', 'max_pain', 'completed_at'];

    protected function casts(): array
    {
        return [
            'regions' => 'array',
            'complaints' => 'array',
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
        return $query->whereNull('completed_at');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}

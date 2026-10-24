<?php

namespace App\Models;

use App\Enums\ProgramStatus;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory, BelongsToClinic;

    /** pathway of the program that follows once the physiotherapy goal is achieved */
    public const MAINTENANCE = 'maintenance';

    protected $fillable = [
        'clinic_id', 'patient_id', 'created_by', 'title', 'status', 'starts_on', 'ends_on',
        'assessment_id', 'pathway', 'needs_monitoring', 'intensity_level', 'evaluation_interval_weeks',
        'next_evaluation_on', 'ended_at', 'end_reason', 'ended_by', 'reviewed_by', 'reviewed_at', 'review_type', 'review_note',
    ];

    public const REVIEW_APPROVED = 'approved';
    public const REVIEW_ADJUSTED = 'adjusted';

    protected function casts(): array
    {
        return [
            'status' => ProgramStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'needs_monitoring' => 'boolean',
            'next_evaluation_on' => 'date',
            'ended_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'assessment_id');
    }

    /** The exercises the patient does now. Items removed by a physiotherapist are left out (their logs stay). */
    public function items(): HasMany
    {
        return $this->hasMany(ProgramExercise::class)->whereNull('removed_at')->orderBy('sort_order');
    }

    /** Every item, also the removed ones (staff history). */
    public function allItems(): HasMany
    {
        return $this->hasMany(ProgramExercise::class)->orderBy('sort_order');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function logs(): HasManyThrough
    {
        return $this->hasManyThrough(ExerciseLog::class, ProgramExercise::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ProgramEvaluation::class);
    }

    /** Active or waiting for reassessment: the patient's current program. */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereIn('status', [ProgramStatus::Active, ProgramStatus::NeedsReview]);
    }

    public function isActive(): bool
    {
        return $this->status === ProgramStatus::Active;
    }

    public function isMaintenance(): bool
    {
        return $this->pathway === self::MAINTENANCE;
    }

    public function isEvaluationDue(): bool
    {
        return $this->isActive() && $this->next_evaluation_on !== null && $this->next_evaluation_on->lte(today());
    }

    /** @param int|null $by the staff member who ended it (null = ended by the system) */
    public function end(string $reason, ?int $by = null): void
    {
        $this->update(['status' => ProgramStatus::Ended, 'ended_at' => now(), 'end_reason' => $reason, 'ended_by' => $by]);
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }
}

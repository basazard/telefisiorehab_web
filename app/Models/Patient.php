<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Goal;
use App\Enums\ServiceModule;
use App\Enums\SpecialCondition;
use App\Support\Onboarding\Assessment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory, BelongsToClinic;

    protected $fillable = [
        'user_id',
        'clinic_id',
        'therapist_id',
        'date_of_birth',
        'gender',
        'height_cm',
        'weight_kg',
        'bmi',
        'occupation',
        'activity_hours_per_day',
        'has_special_condition',
        'special_conditions',
        'special_condition_other',
        'goals',
        'goal_other',
        'notes',
        'profile_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'gender' => Gender::class,
            'special_conditions' => AsEnumCollection::of(SpecialCondition::class),
            'goals' => AsEnumCollection::of(Goal::class),
            'has_special_condition' => 'boolean',
            'profile_completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function isProfileComplete(): bool
    {
        return $this->profile_completed_at !== null;
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn() => Assessment::age($this->date_of_birth?->toDateString()));
    }

    protected function module(): Attribute
    {
        return Attribute::get(fn() => $this->age === null ? null : ServiceModule::fromAge($this->age));
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(Screening::class);
    }
    public function latestScreening(): HasOne
    {
        return $this->hasOne(Screening::class)->latestOfMany();
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /** Active, or paused for reassessment. */
    public function currentProgram(): ?Program
    {
        return $this->programs()->current()->latest('id')->first();
    }

    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ProgramEvaluation::class);
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class);
    }

    /** Records a telekonsultasi / rujukan request unless one of that type is still open. Returns false when one was open. */
    public function requestConsultation(string $type, ?int $assessmentId, ?string $reason = null): bool
    {
        if ($this->consultationRequests()->where('type', $type)->where('status', 'open')->exists()) {
            return false;
        }

        $this->consultationRequests()->create([
            'clinic_id' => $this->clinic_id,
            'assessment_id' => $assessmentId,
            'type' => $type,
            'reason' => filled($reason) ? $reason : null,
            'status' => 'open',
        ]);

        return true;
    }

    public function redFlagChecks(): HasMany
    {
        return $this->hasMany(RedFlagCheck::class);
    }

    public function isRedFlagLocked(): bool
    {
        return $this->redFlagChecks()->whereNotNull('stopped_at')->whereNull('unlocked_at')->exists();
    }
}

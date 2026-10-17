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
}

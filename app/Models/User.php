<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\Role;
use App\Support\SafeMail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $attributes = [
        'role' => 'patient',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'deactivated_at' => 'datetime',
        ];
    }

    /** Registration and "resend": a mail failure is logged and never breaks the action (PRD 11 E1). */
    public function sendEmailVerificationNotification(): void
    {
        SafeMail::send(fn () => parent::sendEmailVerificationNotification(), $this->id);
    }

    /** Forgot password: a mail failure is logged and never breaks the page (PRD 11 E1). */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        SafeMail::send(fn () => parent::sendPasswordResetNotification($token), $this->id);
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /** Patients assigned to this therapist. Not clinic scoped: filter by clinic where it matters. */
    public function assignedPatients(): HasMany
    {
        return $this->hasMany(Patient::class, 'therapist_id')->withoutGlobalScopes();
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }
}

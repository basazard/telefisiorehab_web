<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Screening;
use App\Models\User;

class ScreeningPolicy
{
    public function view(User $user, Screening $screening): bool
    {
        return match ($user->role) {
            Role::Patient => $screening->patient->user_id === $user->id,
            Role::Therapist => $screening->patient->therapist_id === $user->id,
            Role::ClinicAdmin => $user->clinic_id !== null && $screening->clinic_id === $user->clinic_id,
            Role::PlatformAdmin => true,
        };
    }

    /** Only the owning patient can edit, and only while the screening is still in progress. */
    public function update(User $user, Screening $screening): bool
    {
        return $user->role === Role::Patient
            && $screening->patient->user_id === $user->id
            && ! $screening->isCompleted();
    }
}

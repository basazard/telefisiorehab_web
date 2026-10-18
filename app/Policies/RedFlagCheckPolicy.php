<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\RedFlagCheck;
use App\Models\User;

class RedFlagCheckPolicy
{
    /**
     * Only staff of the patient's clinic (or a platform admin) can lift a red-flag stop.
     * Patients without a clinic can only be unlocked by a platform admin.
     */
    public function unlock(User $user, RedFlagCheck $check): bool
    {
        return match ($user->role) {
            Role::Therapist, Role::ClinicAdmin => $user->clinic_id !== null && $user->clinic_id === $check->clinic_id,
            Role::PlatformAdmin => true,
            default => false,
        };
    }
}

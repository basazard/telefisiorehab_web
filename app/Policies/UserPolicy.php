<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/** Staff accounts (PRD 09 S4.3, D6): a clinic admin deactivates or reactivates therapists of the own clinic only. */
class UserPolicy
{
    public function deactivate(User $user, User $target): bool
    {
        return $this->manages($user, $target) && ! $target->isDeactivated();
    }

    public function reactivate(User $user, User $target): bool
    {
        return $this->manages($user, $target) && $target->isDeactivated();
    }

    /** Never themselves, never another admin, never another clinic. */
    private function manages(User $user, User $target): bool
    {
        return $user->role === Role::ClinicAdmin
            && $user->clinic_id !== null
            && $target->role === Role::Therapist
            && $user->id !== $target->id
            && (int) $user->clinic_id === (int) $target->clinic_id;
    }
}

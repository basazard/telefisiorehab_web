<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\User;

/** The clinic pages (PRD 09 S4): only a clinic admin of that clinic. Platform admins have no clinic pages yet. */
class ClinicPolicy
{
    /** Clinic dashboard and team list. */
    public function manage(User $user, Clinic $clinic): bool
    {
        return $user->role === Role::ClinicAdmin && (int) $user->clinic_id === (int) $clinic->id;
    }

    /** Invite a new therapist to the clinic. */
    public function invite(User $user, Clinic $clinic): bool
    {
        return $this->manage($user, $clinic);
    }
}

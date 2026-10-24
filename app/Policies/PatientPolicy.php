<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;

/**
 * Staff access to patients (PRD 09 §4). Staff only reach patients of their own clinic; a therapist opens a patient's
 * detail only once the patient is assigned to them. Platform admins see everything.
 */
class PatientPolicy
{
    /** The staff patient list (therapists also see unassigned patients there, with limited columns). */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [Role::Therapist, Role::ClinicAdmin, Role::PlatformAdmin], true);
    }

    /** The patient's detail: progress, program, screening answers. */
    public function view(User $user, Patient $patient): bool
    {
        return match ($user->role) {
            Role::PlatformAdmin => true,
            Role::ClinicAdmin => $this->sameClinic($user, $patient),
            Role::Therapist => $this->sameClinic($user, $patient) && (int) $patient->therapist_id === (int) $user->id,
            default => false,
        };
    }

    /** Staff notes: whoever may open the detail. */
    public function addNote(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);
    }

    /** A therapist takes an unassigned patient of their own clinic. */
    public function claim(User $user, Patient $patient): bool
    {
        return $user->role === Role::Therapist
            && ! $user->isDeactivated()
            && $this->sameClinic($user, $patient)
            && $patient->therapist_id === null;
    }

    /** Assign or reassign a patient; when given, $therapist must be an active therapist of the patient's clinic. */
    public function assign(User $user, Patient $patient, ?User $therapist = null): bool
    {
        $allowed = $user->role === Role::PlatformAdmin
            || ($user->role === Role::ClinicAdmin && $this->sameClinic($user, $patient));

        if (! $allowed || $therapist === null) {
            return $allowed;
        }

        return $therapist->role === Role::Therapist
            && ! $therapist->isDeactivated()
            && $patient->clinic_id !== null
            && (int) $therapist->clinic_id === (int) $patient->clinic_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Patient $patient): bool
    {
        return false;
    }

    public function delete(User $user, Patient $patient): bool
    {
        return false;
    }

    public function restore(User $user, Patient $patient): bool
    {
        return false;
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }

    private function sameClinic(User $user, Patient $patient): bool
    {
        return $user->clinic_id !== null && (int) $user->clinic_id === (int) $patient->clinic_id;
    }
}

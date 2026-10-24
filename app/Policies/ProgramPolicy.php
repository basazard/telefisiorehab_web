<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\Program;
use App\Models\User;

/**
 * Staff review, adjustment, creation and ending of programs (PRD 09 §4): whoever may open the patient's detail
 * (the assigned therapist, the clinic admin, a platform admin). Patients never change a program here.
 */
class ProgramPolicy
{
    public function __construct(private PatientPolicy $patients)
    {
    }

    /** Approve, adjust, resume. */
    public function update(User $user, Program $program): bool
    {
        return $this->staffOf($user, $program);
    }

    /** End the program, or mark the goal achieved. */
    public function end(User $user, Program $program): bool
    {
        return $this->staffOf($user, $program);
    }

    /** "Buat program" for a patient. */
    public function create(User $user, Patient $patient): bool
    {
        return $this->patients->view($user, $patient);
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Program $program): bool
    {
        return false;
    }

    public function delete(User $user, Program $program): bool
    {
        return false;                                       // never: logs and evaluations hang on it
    }

    public function restore(User $user, Program $program): bool
    {
        return false;
    }

    public function forceDelete(User $user, Program $program): bool
    {
        return false;
    }

    private function staffOf(User $user, Program $program): bool
    {
        $patient = Patient::query()->withoutGlobalScopes()->find($program->patient_id);

        return $patient !== null && $this->patients->view($user, $patient);
    }
}

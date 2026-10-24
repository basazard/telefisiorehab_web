<?php

namespace App\Support\Staff;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Who treats a patient (patients.therapist_id). Used by the patient list, the clinic dashboard and staff
 * deactivation. Each method authorises through PatientPolicy (403 otherwise) and writes an audit row.
 */
class Assignment
{
    /** The id comes from the browser: load without the clinic scope so the policy answers (403, not 404). */
    public static function find(int $patientId): Patient
    {
        return Patient::query()->withoutGlobalScope('clinic')->findOrFail($patientId);
    }

    /** A therapist takes an unassigned patient. False when someone else was faster. */
    public static function claim(User $therapist, Patient $patient): bool
    {
        Gate::forUser($therapist)->authorize('claim', $patient);

        // atomic: only succeeds while nobody else has claimed the patient
        $claimed = Patient::query()->withoutGlobalScopes()->whereKey($patient->id)->whereNull('therapist_id')
            ->update(['therapist_id' => $therapist->id]) === 1;

        if ($claimed) {
            Audit::record($therapist, $patient, Audit::ASSIGNED);
        }

        return $claimed;
    }

    /** A clinic admin (or platform admin) assigns or reassigns the patient to a therapist of its clinic. */
    public static function assign(User $by, Patient $patient, User $therapist): void
    {
        Gate::forUser($by)->authorize('assign', [$patient, $therapist]);

        $patient->forceFill(['therapist_id' => $therapist->id])->save();
        Audit::record($by, $patient, Audit::ASSIGNED);
    }

    /** Back to "unassigned" (used when a therapist is deactivated). */
    public static function release(User $by, Patient $patient): void
    {
        Gate::forUser($by)->authorize('assign', $patient);

        $patient->forceFill(['therapist_id' => null])->save();
        Audit::record($by, $patient, Audit::ASSIGNED);
    }

    /** Active therapists of a clinic, for the assign dropdowns. */
    public static function therapists(?int $clinicId)
    {
        return User::query()
            ->where('role', Role::Therapist)
            ->whereNull('deactivated_at')
            ->when($clinicId !== null, fn ($q) => $q->where('clinic_id', $clinicId))
            ->orderBy('name')
            ->get(['id', 'name', 'clinic_id']);
    }
}

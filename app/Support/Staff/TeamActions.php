<?php

namespace App\Support\Staff;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Deactivating and reactivating staff of a clinic (PRD 09 S4.3). Authorised through UserPolicy. */
class TeamActions
{
    /**
     * Moves the therapist's patients to $moveTo (or releases them when null), then deactivates the account.
     * Returns the number of patients moved or released.
     */
    public static function deactivate(User $admin, User $therapist, ?User $moveTo): int
    {
        Gate::forUser($admin)->authorize('deactivate', $therapist);

        if ($moveTo && $moveTo->id === $therapist->id) {
            throw ValidationException::withMessages(['moveTo' => 'Pilih fisioterapis lain.']);
        }

        return DB::transaction(function () use ($admin, $therapist, $moveTo) {
            $patients = $therapist->assignedPatients()->get();

            foreach ($patients as $patient) {
                $moveTo ? Assignment::assign($admin, $patient, $moveTo) : Assignment::release($admin, $patient);
            }

            $therapist->forceFill(['deactivated_at' => now()])->save();

            return $patients->count();
        });
    }

    public static function reactivate(User $admin, User $therapist): void
    {
        Gate::forUser($admin)->authorize('reactivate', $therapist);

        $therapist->forceFill(['deactivated_at' => null])->save();
    }
}

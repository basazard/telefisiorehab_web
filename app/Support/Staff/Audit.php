<?php

namespace App\Support\Staff;

use App\Models\Patient;
use App\Models\PatientAccessLog;
use App\Models\User;

/** Records staff access to and changes of a patient's data (PRD 09 S2.1). No UI yet. */
class Audit
{
    public const VIEWED_DETAIL = 'viewed_detail';
    public const ASSIGNED = 'assigned';
    public const NOTE_ADDED = 'note_added';

    public static function record(User $user, Patient $patient, string $action): PatientAccessLog
    {
        return PatientAccessLog::query()->create(['user_id' => $user->id, 'patient_id' => $patient->id, 'action' => $action]);
    }
}

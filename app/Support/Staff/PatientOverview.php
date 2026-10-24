<?php

namespace App\Support\Staff;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * One row per patient for the staff dashboard and patient list. Loads everything in a fixed number of queries
 * (no N+1) and hides what the viewer may not see: a therapist gets only name, age and risk tier for an
 * unassigned patient (PatientPolicy::view decides).
 */
class PatientOverview
{
    /** Days of logs loaded: enough for the 7-day adherence and the 5-day inactivity rule. */
    private const LOG_DAYS = 14;

    /**
     * Patients the viewer may list. Explicit clinic filter on top of the global scope; a therapist sees their own
     * patients and the unassigned ones of the clinic.
     */
    public static function visibleTo(User $viewer): Builder
    {
        $query = self::inClinicOf($viewer);

        if ($viewer->role === Role::Therapist) {
            $query->where(fn (Builder $q) => $q->where('therapist_id', $viewer->id)->orWhereNull('therapist_id'));
        }

        return $query;
    }

    /**
     * Patients of the viewer's clinic (all of them for a platform admin). Load a patient id from the browser through
     * this, then ask PatientPolicy: another clinic's patient is a 404, so its existence stays hidden.
     */
    public static function inClinicOf(User $viewer): Builder
    {
        $query = Patient::query();

        if ($viewer->role !== Role::PlatformAdmin) {
            $query->where('patients.clinic_id', $viewer->clinic_id);
        }

        return $query;
    }

    /** @return Collection<int, array<string, mixed>> */
    public static function rows(Builder $patients, User $viewer): Collection
    {
        return $patients
            ->with([
                'user:id,name',
                'therapist:id,name',
                'latestRiskAssessment',
                'latestEvaluation',
                'programs' => fn ($q) => $q->current()->latest('id'),
                'exerciseLogs' => fn ($q) => $q->select('id', 'patient_id', 'performed_at')
                    ->where('performed_at', '>=', today()->subDays(self::LOG_DAYS)),
            ])
            ->withMax('exerciseLogs as last_log_at', 'performed_at')
            ->withExists(['consultationRequests as has_open_consultation' => fn ($q) => $q->where('status', 'open')])
            ->withExists(['redFlagChecks as is_locked' => fn ($q) => $q->whereNotNull('stopped_at')->whereNull('unlocked_at')])
            ->get()
            ->map(fn (Patient $patient) => self::row($patient, $viewer));
    }

    /** @return array<string, mixed> */
    public static function row(Patient $patient, User $viewer): array
    {
        $row = [
            'id' => $patient->id,
            'name' => $patient->user?->name ?? '—',
            'age' => $patient->age,
            'module' => $patient->module?->label(),
            'tier' => $patient->latestRiskAssessment?->tier,
            'assigned' => $patient->therapist_id !== null,
            'mine' => (int) $patient->therapist_id === (int) $viewer->id,
            'limited' => ! $viewer->can('view', $patient),
            'claimable' => $viewer->can('claim', $patient),
        ];

        if ($row['limited']) {
            return $row + ['pathway' => null, 'adherence' => null, 'last_activity' => null, 'next_evaluation' => null,
                'reasons' => [], 'therapist' => null, 'program_status' => null];
        }

        $program = $patient->programs->first();
        $logDates = $patient->exerciseLogs->pluck('performed_at')->all();

        return $row + [
            'pathway' => $program?->pathway,
            'program_status' => $program?->status,
            'adherence' => $program ? Attention::adherence($program, $logDates) : null,
            'last_activity' => $patient->last_log_at ? substr((string) $patient->last_log_at, 0, 10) : null,
            'next_evaluation' => $program?->isActive() ? $program->next_evaluation_on : null,
            'therapist' => $patient->therapist?->name,
            'reasons' => Attention::reasons(
                $program,
                $logDates,
                $patient->latestEvaluation,
                (bool) $patient->has_open_consultation,
                (bool) $patient->is_locked,
            ),
        ];
    }
}

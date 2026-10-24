<?php

namespace App\Support\Staff;

use App\Enums\ProgramStatus;
use App\Models\Exercise;
use App\Models\Patient;
use App\Models\Program;
use App\Models\ProgramExercise;
use App\Models\User;
use App\Support\Onboarding\Intervention;
use App\Support\Onboarding\Programs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Program review, adjustment and creation by a physiotherapist (PRD 09 S3). Callers authorise first
 * (ProgramPolicy); these methods enforce the safety rules, run in a transaction and write an audit row.
 * Nothing here deletes a program, an item, a log or an evaluation.
 */
class ProgramActions
{
    /** "Setujui program": the patient sees "Disetujui fisioterapis". */
    public static function approve(User $user, Program $program, ?string $note = null): void
    {
        self::requireStatus($program, [ProgramStatus::Active]);

        DB::transaction(function () use ($user, $program, $note) {
            self::markReviewed($user, $program, Program::REVIEW_APPROVED, filled($note) ? trim($note) : null);
            Audit::record($user, $program->patient()->withoutGlobalScopes()->first(), Audit::PROGRAM_REVIEWED);
        });
    }

    /**
     * "Sesuaikan": level, doses, removed items and added exercises in one go, with a note.
     *
     * @param array<int, array<string, mixed>> $doses item id => [sets, reps, hold_seconds, minutes]
     * @param int[] $remove item ids
     * @param int[] $add exercise ids
     */
    public static function adjust(User $user, Program $program, string $note, ?int $level = null, array $doses = [], array $remove = [], array $add = []): void
    {
        self::requireStatus($program, [ProgramStatus::Active]);
        self::requireNote($note);

        $items = $program->items()->with('exercise')->get()->keyBy('id');
        $levelChanged = $level !== null && $level !== (int) $program->intensity_level;

        if ($level !== null && ($level < 1 || $level > Intervention::MAX_LEVEL)) {
            throw ValidationException::withMessages(['level' => 'Level 1–'.Intervention::MAX_LEVEL.'.']);
        }

        $errors = [];
        $changedDoses = [];
        foreach ($doses as $itemId => $dose) {
            $item = $items->get($itemId) ?? throw ValidationException::withMessages(['doses' => 'Latihan tidak ada di program ini.']);
            $unit = $item->exercise->dose_unit;

            foreach (ProgramRules::doseErrors($unit, $item->exercise->type->value, $dose) as $field => $message) {
                $errors["doses.$itemId.$field"] = $message;
            }

            $normalized = ProgramRules::normalizeDose($unit, $dose);
            if (array_diff_assoc(array_map('strval', $normalized), array_map('strval', $item->only(array_keys($normalized))))) {
                $changedDoses[$itemId] = $normalized;
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $remove = array_values(array_unique(array_map('intval', $remove)));
        if (array_diff($remove, $items->keys()->all())) {
            throw ValidationException::withMessages(['remove' => 'Latihan tidak ada di program ini.']);
        }
        if (count($remove) >= $items->count() && $add === []) {
            throw ValidationException::withMessages(['remove' => 'Program harus berisi minimal satu latihan.']);
        }

        $exercises = self::library($program->patient()->withoutGlobalScopes()->first())->whereIn('id', $add)->get();
        if ($exercises->count() !== count(array_unique($add))) {
            throw ValidationException::withMessages(['add' => 'Latihan tidak tersedia untuk klinik ini.']);
        }

        if (! $levelChanged && ! $changedDoses && ! $remove && $exercises->isEmpty()) {
            throw ValidationException::withMessages(['adjust' => 'Belum ada perubahan.']);
        }

        DB::transaction(function () use ($user, $program, $note, $level, $levelChanged, $changedDoses, $remove, $exercises) {
            if ($levelChanged) {
                $program->update(['intensity_level' => $level]);
                Programs::redose($program);                         // the standard dose of the new level first ...
            }

            foreach ($changedDoses as $itemId => $dose) {          // ... then the physiotherapist's own doses
                ProgramExercise::query()->whereKey($itemId)->update($dose);
            }

            if ($remove) {
                ProgramExercise::query()->whereIn('id', $remove)->update(['removed_at' => now()]);
            }

            $order = (int) $program->allItems()->max('sort_order');
            foreach ($exercises as $exercise) {
                $program->allItems()->create(['exercise_id' => $exercise->id, 'sort_order' => ++$order]
                    + Intervention::dose(Intervention::doseTier($program->pathway), $exercise->dose_unit, $exercise->type->value, $program->intensity_level));
            }

            self::markReviewed($user, $program, Program::REVIEW_ADJUSTED, trim($note));
            Audit::record($user, $program->patient()->withoutGlobalScopes()->first(), Audit::PROGRAM_ADJUSTED);
        });
    }

    /** "Lanjutkan dengan penyesuaian": a paused program becomes active again, optionally one level lighter. */
    public static function resume(User $user, Program $program, string $note, bool $lighter = false): void
    {
        self::requireStatus($program, [ProgramStatus::NeedsReview]);
        self::requireNote($note);

        DB::transaction(function () use ($user, $program, $note, $lighter) {
            $program->update([
                'status' => ProgramStatus::Active,
                'intensity_level' => $lighter ? max(1, $program->intensity_level - 1) : $program->intensity_level,
                'next_evaluation_on' => today()->addWeeks($program->evaluation_interval_weeks),
            ]);

            if ($lighter) {
                Programs::redose($program);
            }

            self::markReviewed($user, $program, Program::REVIEW_ADJUSTED, trim($note));
            Audit::record($user, $program->patient()->withoutGlobalScopes()->first(), Audit::PROGRAM_RESUMED);
        });
    }

    /** "Akhiri program": the patient is asked to reassess. Logs and evaluations stay. */
    public static function end(User $user, Program $program, string $reason): void
    {
        self::requireStatus($program, [ProgramStatus::Active, ProgramStatus::NeedsReview]);
        self::requireNote($reason, 'reason');

        DB::transaction(function () use ($user, $program, $reason) {
            $program->end(trim($reason), $user->id);
            Audit::record($user, $program->patient()->withoutGlobalScopes()->first(), Audit::PROGRAM_ENDED);
        });
    }

    /** "Tandai tujuan tercapai": ends the program and starts the Maintenance Program (PRD 07). */
    public static function goalAchieved(User $user, Program $program): Program
    {
        self::requireStatus($program, [ProgramStatus::Active]);

        if ($program->isMaintenance()) {
            throw ValidationException::withMessages(['goal' => 'Program ini sudah program pemeliharaan.']);
        }

        if (! $program->assessment) {
            throw ValidationException::withMessages(['goal' => 'Program tidak terhubung ke assessment.']);
        }

        return DB::transaction(function () use ($user, $program) {
            $program->end('Tujuan fisioterapi tercapai', $user->id);
            $maintenance = Programs::buildMaintenance($program);
            Audit::record($user, $program->patient()->withoutGlobalScopes()->first(), Audit::GOAL_ACHIEVED);

            return $maintenance;
        });
    }

    /**
     * "Buat program" for a patient without one (pediatric first). Guided, monitored, already reviewed.
     *
     * @param array<int, array<string, mixed>> $items exercise id => dose
     */
    public static function create(User $user, Patient $patient, string $title, array $items, ?string $note = null): Program
    {
        $assessment = $patient->latestRiskAssessment()->first();
        $refusal = ProgramRules::createRefusal($patient->isRedFlagLocked(), $assessment?->tier, $patient->programs()->current()->exists());

        if ($refusal) {
            throw ValidationException::withMessages(['create' => $refusal]);
        }

        if (mb_strlen(trim($title)) < 3) {
            throw ValidationException::withMessages(['title' => 'Judul program minimal 3 karakter.']);
        }

        $exercises = self::library($patient)->whereIn('id', array_keys($items))->get()->keyBy('id');
        if ($items === [] || $exercises->count() !== count($items)) {
            throw ValidationException::withMessages(['items' => 'Pilih minimal satu latihan dari pustaka.']);
        }

        $errors = [];
        foreach ($items as $exerciseId => $dose) {
            $exercise = $exercises[$exerciseId];
            foreach (ProgramRules::doseErrors($exercise->dose_unit, $exercise->type->value, $dose) as $field => $message) {
                $errors["items.$exerciseId.$field"] = $message;
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($user, $patient, $assessment, $title, $items, $exercises, $note) {
            $program = Program::query()->create([
                'clinic_id' => $patient->clinic_id,
                'patient_id' => $patient->id,
                'assessment_id' => $assessment->id,
                'created_by' => $user->id,
                'title' => trim($title),
                'status' => ProgramStatus::Active,
                'pathway' => 'guided',
                'needs_monitoring' => true,
                'intensity_level' => 1,
                'evaluation_interval_weeks' => ProgramRules::CREATED_EVALUATION_WEEKS,
                'starts_on' => today(),
                'next_evaluation_on' => today()->addWeeks(ProgramRules::CREATED_EVALUATION_WEEKS),
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_type' => Program::REVIEW_ADJUSTED,
                'review_note' => filled($note) ? trim($note) : null,
            ]);

            $order = 0;
            foreach ($items as $exerciseId => $dose) {
                $program->allItems()->create(['exercise_id' => $exerciseId, 'sort_order' => $order++]
                    + ProgramRules::normalizeDose($exercises[$exerciseId]->dose_unit, $dose));
            }

            Audit::record($user, $patient, Audit::PROGRAM_CREATED);

            return $program;
        });
    }

    /** Exercises staff may add: the global library and the patient's clinic's own. */
    public static function library(Patient $patient): Builder
    {
        return Exercise::query()->withoutGlobalScopes()
            ->where(fn (Builder $q) => $q->whereNull('clinic_id')->when($patient->clinic_id, fn ($q) => $q->orWhere('clinic_id', $patient->clinic_id)))
            ->whereNotNull('type')
            ->orderBy('name');
    }

    private static function markReviewed(User $user, Program $program, string $type, ?string $note): void
    {
        $program->update(['reviewed_by' => $user->id, 'reviewed_at' => now(), 'review_type' => $type, 'review_note' => $note]);
    }

    /** @param ProgramStatus[] $allowed */
    private static function requireStatus(Program $program, array $allowed): void
    {
        if (! in_array($program->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'Aksi ini tidak tersedia untuk program berstatus '.$program->status->label().'.']);
        }
    }

    private static function requireNote(?string $note, string $key = 'note'): void
    {
        if ($error = ProgramRules::noteError($note)) {
            throw ValidationException::withMessages([$key => $error]);
        }
    }
}

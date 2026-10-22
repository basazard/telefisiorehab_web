<?php

namespace App\Support\Staff;

use App\Enums\ProgramStatus;
use App\Models\Program;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\Intervention;
use App\Support\Onboarding\Monitoring;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * "Perlu perhatian" (PRD 09 §5.3): why a patient needs a physiotherapist's attention. Pure: works on the given
 * program, log dates and evaluation, never queries.
 */
class Attention
{
    public const OPEN_CONSULTATION = 'open_consultation';
    public const RED_FLAG_LOCKED = 'red_flag_locked';
    public const NEEDS_REVIEW = 'needs_review';
    public const EVALUATION_OVERDUE = 'evaluation_overdue';
    public const LOW_ADHERENCE = 'low_adherence';
    public const WORSENING = 'worsening';
    public const NO_ACTIVITY = 'no_activity';

    public const ADHERENCE_DAYS = 7;          // adherence window, the Monitoring PRD value
    public const NO_ACTIVITY_DAYS = 5;        // DRAFT: no log for 5 days while a program is active (clinic to confirm, D5)

    /** In order of urgency. */
    public const LABELS = [
        self::RED_FLAG_LOCKED => 'Red flag: program dihentikan',
        self::OPEN_CONSULTATION => 'Permintaan konsultasi terbuka',
        self::NEEDS_REVIEW => 'Program perlu ditinjau',
        self::WORSENING => 'Kondisi memburuk',
        self::EVALUATION_OVERDUE => 'Evaluasi terlambat',
        self::LOW_ADHERENCE => 'Kepatuhan rendah',
        self::NO_ACTIVITY => 'Tidak ada aktivitas',
    ];

    /**
     * @param Program|null $program the current program (active or needs_review)
     * @param array<int, DateTimeInterface|string> $logDates when the patient logged an exercise
     * @return string[] reason keys, most urgent first
     */
    public static function reasons(
        ?Program $program,
        array $logDates,
        ?ProgramEvaluation $latestEvaluation,
        bool $openConsultation,
        bool $redFlagLocked,
        ?DateTimeInterface $today = null,
    ): array {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $found = [];

        if ($redFlagLocked) {
            $found[] = self::RED_FLAG_LOCKED;
        }

        if ($openConsultation) {
            $found[] = self::OPEN_CONSULTATION;
        }

        if ($program?->status === ProgramStatus::NeedsReview) {
            $found[] = self::NEEDS_REVIEW;
        }

        if ($latestEvaluation && self::isWorse($latestEvaluation)) {
            $found[] = self::WORSENING;
        }

        if ($program?->status === ProgramStatus::Active) {
            if ($program->next_evaluation_on && $program->next_evaluation_on->lt($today)) {
                $found[] = self::EVALUATION_OVERDUE;
            }

            $adherence = self::adherence($program, $logDates, $today);

            if ($adherence !== null && $adherence < Monitoring::LOW_ADHERENCE) {
                $found[] = self::LOW_ADHERENCE;
            }

            if (self::daysWithoutActivity($program, $logDates, $today) >= self::NO_ACTIVITY_DAYS) {
                $found[] = self::NO_ACTIVITY;
            }
        }

        return array_values(array_intersect(array_keys(self::LABELS), $found));
    }

    /**
     * % of days with a log over the last 7 days, counted from the program start when it is younger.
     * Null while the program has not run a full day yet.
     */
    public static function adherence(Program $program, array $logDates, ?DateTimeInterface $today = null): ?int
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $from = $today->subDays(self::ADHERENCE_DAYS - 1);
        $start = self::start($program);

        if ($start && $start->gt($from)) {
            $from = $start;
        }

        if ($from->gte($today)) {
            return null;
        }

        return Intervention::adherence($logDates, $from, $today);
    }

    /** The latest log date, as a date string, or null. */
    public static function lastActivity(array $logDates): ?string
    {
        $days = array_map(fn ($d) => $d instanceof DateTimeInterface ? $d->format('Y-m-d') : substr((string) $d, 0, 10), $logDates);

        return $days ? max($days) : null;
    }

    public static function label(string $reason): string
    {
        return self::LABELS[$reason] ?? $reason;
    }

    private static function isWorse(ProgramEvaluation $evaluation): bool
    {
        return $evaluation->global_change === 'worse'
            || in_array($evaluation->outcome, [Monitoring::OUTCOME_REASSESS, Monitoring::OUTCOME_REFERRAL], true);
    }

    /** Days since the last log, or since the program started when nothing was logged. */
    private static function daysWithoutActivity(Program $program, array $logDates, CarbonImmutable $today): int
    {
        $last = self::lastActivity($logDates);
        $since = $last ? CarbonImmutable::parse($last) : self::start($program);

        return $since ? (int) $since->startOfDay()->diffInDays($today) : 0;
    }

    private static function start(Program $program): ?CarbonImmutable
    {
        $start = $program->starts_on ?? $program->created_at;

        return $start ? CarbonImmutable::instance($start)->startOfDay() : null;
    }
}

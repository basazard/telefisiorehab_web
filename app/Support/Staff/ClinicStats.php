<?php

namespace App\Support\Staff;

use App\Enums\ProgramStatus;
use App\Enums\RiskTier;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The clinic dashboard figures (PRD 09 S4.1). Pure: works on the PatientOverview rows of the clinic, no queries.
 */
class ClinicStats
{
    public const NEW_DAYS = 30;

    /** @param Collection<int, array<string, mixed>> $rows */
    public static function summary(Collection $rows, ?DateTimeInterface $today = null): array
    {
        $since = Carbon::instance($today ?? now())->startOfDay()->subDays(self::NEW_DAYS);
        $has = fn (string $reason) => $rows->filter(fn ($r) => in_array($reason, $r['reasons'], true))->count();

        return [
            'active' => $rows->filter(fn ($r) => in_array($r['program_status'], [ProgramStatus::Active, ProgramStatus::NeedsReview], true))->count(),
            'new' => $rows->filter(fn ($r) => $r['joined_at'] && $r['joined_at']->gte($since))->count(),
            'adherence' => self::average($rows),
            'review' => $rows->filter(fn ($r) => $r['program_status'] === ProgramStatus::NeedsReview || $r['unreviewed'])->count(),
            'consultations' => $has(Attention::OPEN_CONSULTATION),
            'locked' => $has(Attention::RED_FLAG_LOCKED),
            'overdue' => $has(Attention::EVALUATION_OVERDUE),
        ];
    }

    /**
     * Patients per risk tier, for the doughnut. Patients without a tier yet are counted as "none".
     *
     * @return array<string, int> low, moderate, high, none
     */
    public static function tiers(Collection $rows): array
    {
        $counts = array_fill_keys([...array_map(fn ($t) => $t->value, RiskTier::cases()), 'none'], 0);

        foreach ($rows as $row) {
            $counts[$row['tier']?->value ?? 'none']++;
        }

        return $counts;
    }

    /**
     * Per therapist: assigned patients, how many need attention, average adherence.
     *
     * @param iterable<int, object{id: int, name: string}> $therapists
     * @return array<int, array{id: int, name: string, patients: int, attention: int, adherence: ?int}>
     */
    public static function workload(Collection $rows, iterable $therapists): array
    {
        $workload = [];

        foreach ($therapists as $therapist) {
            $own = $rows->filter(fn ($r) => (int) $r['therapist_id'] === (int) $therapist->id);
            $workload[] = [
                'id' => $therapist->id,
                'name' => $therapist->name,
                'patients' => $own->count(),
                'attention' => $own->filter(fn ($r) => $r['reasons'] !== [])->count(),
                'adherence' => self::average($own),
            ];
        }

        return $workload;
    }

    /** Average 7-day adherence of the patients with an active program, rounded; null when there is none. */
    private static function average(Collection $rows): ?int
    {
        $values = $rows->pluck('adherence')->filter(fn ($v) => $v !== null);

        return $values->isEmpty() ? null : (int) round($values->avg());
    }
}

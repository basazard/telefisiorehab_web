<?php

namespace App\Support\Staff;

use App\Support\Onboarding\Intervention;
use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Figures for the "Progres" tab of the patient detail (PRD 09 S2.2). Pure: works on the given dates and values,
 * never queries. Charts get these arrays as JSON.
 */
class Progress
{
    public const MAX_WEEKS = 8;          // weeks shown in the adherence chart
    public const PAIN_DAYS = 56;         // days shown in the pain chart (8 weeks)

    /**
     * % of days with at least one log, per 7-day week since the program start (the last MAX_WEEKS weeks). The
     * running week only counts the days up to today.
     *
     * @param array<int, DateTimeInterface|string> $logDates
     * @return array<int, array{week: int, from: string, to: string, percent: int}>
     */
    public static function weeklyAdherence(array $logDates, DateTimeInterface $start, ?DateTimeInterface $today = null): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();
        $start = CarbonImmutable::instance($start)->startOfDay();

        if ($start->gt($today)) {
            return [];
        }

        $weeks = [];

        for ($from = $start, $n = 1; $from->lte($today); $from = $from->addDays(7), $n++) {
            $to = $from->addDays(6)->min($today);
            $weeks[] = ['week' => $n, 'from' => $from->toDateString(), 'to' => $to->toDateString(),
                'percent' => Intervention::adherence($logDates, $from, $to)];
        }

        return array_slice($weeks, -self::MAX_WEEKS);
    }

    /**
     * Pain over time: the average pain of the logs of each day and the pain of each evaluation, on one date axis.
     *
     * @param array<int, array{date: DateTimeInterface|string, pain: ?int}> $logs
     * @param array<int, array{date: DateTimeInterface|string, pain: ?int}> $evaluations
     * @return array{dates: string[], logs: array<int, ?float>, evaluations: array<int, ?int>}
     */
    public static function painTrend(array $logs, array $evaluations, ?DateTimeInterface $today = null): array
    {
        $since = CarbonImmutable::instance($today ?? now())->startOfDay()->subDays(self::PAIN_DAYS - 1)->toDateString();
        $day = fn ($d) => $d instanceof DateTimeInterface ? $d->format('Y-m-d') : substr((string) $d, 0, 10);

        $logPain = [];
        foreach ($logs as $log) {
            if ($log['pain'] !== null && $day($log['date']) >= $since) {
                $logPain[$day($log['date'])][] = $log['pain'];
            }
        }

        $evalPain = [];
        foreach ($evaluations as $evaluation) {
            if ($evaluation['pain'] !== null && $day($evaluation['date']) >= $since) {
                $evalPain[$day($evaluation['date'])] = $evaluation['pain'];   // the latest of the day wins
            }
        }

        $dates = array_unique([...array_keys($logPain), ...array_keys($evalPain)]);
        sort($dates);

        return [
            'dates' => $dates,
            'logs' => array_map(fn ($d) => isset($logPain[$d]) ? round(array_sum($logPain[$d]) / count($logPain[$d]), 1) : null, $dates),
            'evaluations' => array_map(fn ($d) => $evalPain[$d] ?? null, $dates),
        ];
    }
}

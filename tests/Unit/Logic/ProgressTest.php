<?php

use App\Support\Staff\Progress;
use Carbon\CarbonImmutable;

// Pure figures of the "Progres" tab, with a fixed "today".

function progressToday(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-10-22');
}

it('gives the adherence of each 7-day week since the program start, the running week up to today', function () {
    // started 2026-10-08: week 1 = 08–14, week 2 = 15–21, week 3 = 22 (today only)
    $logs = ['2026-10-08', '2026-10-09', '2026-10-10', '2026-10-11', '2026-10-12', '2026-10-13', '2026-10-14',   // 7 of 7
        '2026-10-15', '2026-10-17', '2026-10-19 18:00:00',                                                          // 3 of 7
        '2026-10-22'];                                                                                             // 1 of 1

    expect(Progress::weeklyAdherence($logs, CarbonImmutable::parse('2026-10-08'), progressToday()))->toBe([
        ['week' => 1, 'from' => '2026-10-08', 'to' => '2026-10-14', 'percent' => 100],
        ['week' => 2, 'from' => '2026-10-15', 'to' => '2026-10-21', 'percent' => 43],
        ['week' => 3, 'from' => '2026-10-22', 'to' => '2026-10-22', 'percent' => 100],
    ]);
});

it('keeps only the last 8 weeks', function () {
    $weeks = Progress::weeklyAdherence([], progressToday()->subWeeks(12), progressToday());

    expect($weeks)->toHaveCount(Progress::MAX_WEEKS)
        ->and($weeks[0]['week'])->toBe(6)
        ->and(array_column($weeks, 'percent'))->each->toBe(0);
});

it('stops the weeks at the given end (an ended program)', function () {
    $weeks = Progress::weeklyAdherence(['2026-10-02'], CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-03'));

    expect($weeks)->toBe([['week' => 1, 'from' => '2026-10-01', 'to' => '2026-10-03', 'percent' => 33]]);
});

it('has no weeks before the program starts', function () {
    expect(Progress::weeklyAdherence([], progressToday()->addDay(), progressToday()))->toBe([]);
});

it('averages the pain of the logs per day and puts the evaluations on the same dates', function () {
    $trend = Progress::painTrend(
        [
            ['date' => '2026-10-20 07:00:00', 'pain' => 4],
            ['date' => '2026-10-20 07:10:00', 'pain' => 5],
            ['date' => '2026-10-21 07:00:00', 'pain' => 3],
            ['date' => '2026-10-21 07:10:00', 'pain' => null],       // pain not given: ignored
        ],
        [['date' => '2026-10-19 19:00:00', 'pain' => 6], ['date' => '2026-10-21 19:00:00', 'pain' => 2]],
        progressToday(),
    );

    expect($trend)->toBe([
        'dates' => ['2026-10-19', '2026-10-20', '2026-10-21'],
        'logs' => [null, 4.5, 3.0],
        'evaluations' => [6, null, 2],
    ]);
});

it('leaves out pain older than 8 weeks', function () {
    $old = progressToday()->subDays(Progress::PAIN_DAYS)->toDateString();
    $first = progressToday()->subDays(Progress::PAIN_DAYS - 1)->toDateString();

    expect(Progress::painTrend([['date' => $old, 'pain' => 7], ['date' => $first, 'pain' => 5]], [], progressToday())['dates'])->toBe([$first]);
});

it('returns empty series without data', function () {
    expect(Progress::painTrend([], [], progressToday()))->toBe(['dates' => [], 'logs' => [], 'evaluations' => []]);
});

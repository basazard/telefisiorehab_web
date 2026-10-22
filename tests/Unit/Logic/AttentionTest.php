<?php

use App\Enums\ProgramStatus;
use App\Models\Program;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\Monitoring;
use App\Support\Staff\Attention;
use Carbon\CarbonImmutable;
use Tests\TestCase;

// Pure rules: unsaved models, a fixed "today", no database queries (the app is booted for the date casts only).
uses(TestCase::class);

function attToday(): CarbonImmutable
{
    return CarbonImmutable::parse('2026-10-22');
}

function attProgram(array $attributes = []): Program
{
    return new Program($attributes + [
        'status' => ProgramStatus::Active,
        'starts_on' => '2026-10-01',
        'next_evaluation_on' => '2026-10-29',
    ]);
}

/** Logs on each of the given days before attToday(). */
function attLogs(int ...$days): array
{
    return array_map(fn (int $d) => attToday()->subDays($d)->setTime(7, 30), $days);
}

function attReasons(?Program $program = null, array $logs = [], ?ProgramEvaluation $evaluation = null, bool $consultation = false, bool $locked = false): array
{
    return Attention::reasons($program, $logs, $evaluation, $consultation, $locked, attToday());
}

it('finds nothing for a patient who exercises every day', function () {
    expect(attReasons(attProgram(), attLogs(0, 1, 2, 3, 4, 5, 6)))->toBe([]);
});

it('finds nothing for a patient without a program and without open issues', function () {
    expect(attReasons())->toBe([]);
});

it('flags an open consultation and a red-flag lock, most urgent first', function () {
    expect(attReasons(consultation: true, locked: true))->toBe([Attention::RED_FLAG_LOCKED, Attention::OPEN_CONSULTATION]);
});

it('flags a program that needs review', function () {
    expect(attReasons(attProgram(['status' => ProgramStatus::NeedsReview])))->toContain(Attention::NEEDS_REVIEW);
});

it('flags an evaluation date before today, not one due today', function () {
    $daily = attLogs(0, 1, 2, 3, 4, 5, 6);

    expect(attReasons(attProgram(['next_evaluation_on' => '2026-10-21']), $daily))->toBe([Attention::EVALUATION_OVERDUE])
        ->and(attReasons(attProgram(['next_evaluation_on' => '2026-10-22']), $daily))->toBe([]);
});

it('flags adherence below 50 % over the last 7 days', function () {
    // 3 of 7 days = 43 %, 4 of 7 days = 57 %
    expect(attReasons(attProgram(), attLogs(0, 2, 4)))->toContain(Attention::LOW_ADHERENCE)
        ->and(attReasons(attProgram(), attLogs(0, 2, 4, 6)))->not->toContain(Attention::LOW_ADHERENCE)
        ->and(Monitoring::LOW_ADHERENCE)->toBe(50);
});

it('counts adherence from the program start when the program is younger than 7 days', function () {
    $program = attProgram(['starts_on' => '2026-10-20']);   // 3 days: 20, 21, 22

    expect(Attention::adherence($program, attLogs(1, 2), attToday()))->toBe(67)
        ->and(attReasons($program, attLogs(1, 2)))->toBe([]);
});

it('does not judge adherence on the day a program starts', function () {
    expect(Attention::adherence(attProgram(['starts_on' => '2026-10-22']), [], attToday()))->toBeNull()
        ->and(attReasons(attProgram(['starts_on' => '2026-10-22'])))->toBe([]);
});

it('flags a worse latest evaluation', function () {
    $worse = new ProgramEvaluation(['global_change' => 'worse', 'outcome' => Monitoring::OUTCOME_MODIFY]);
    $reassess = new ProgramEvaluation(['global_change' => 'same', 'outcome' => Monitoring::OUTCOME_REASSESS]);
    $referral = new ProgramEvaluation(['global_change' => 'same', 'outcome' => Monitoring::OUTCOME_REFERRAL]);
    $better = new ProgramEvaluation(['global_change' => 'better', 'outcome' => Monitoring::OUTCOME_PROGRESS]);

    expect(attReasons(evaluation: $worse))->toBe([Attention::WORSENING])
        ->and(attReasons(evaluation: $reassess))->toBe([Attention::WORSENING])
        ->and(attReasons(evaluation: $referral))->toBe([Attention::WORSENING])
        ->and(attReasons(evaluation: $better))->toBe([]);
});

it('flags no activity after 5 days (DRAFT value)', function () {
    expect(Attention::NO_ACTIVITY_DAYS)->toBe(5)
        ->and(attReasons(attProgram(), attLogs(5, 6)))->toContain(Attention::NO_ACTIVITY)
        ->and(attReasons(attProgram(), attLogs(4)))->not->toContain(Attention::NO_ACTIVITY);
});

it('counts inactivity from the program start when nothing was logged', function () {
    expect(attReasons(attProgram(['starts_on' => '2026-10-17'])))->toContain(Attention::NO_ACTIVITY)
        ->and(attReasons(attProgram(['starts_on' => '2026-10-19'])))->not->toContain(Attention::NO_ACTIVITY);
});

it('ignores adherence, inactivity and the evaluation date of a program that is not active', function () {
    $paused = attProgram(['status' => ProgramStatus::NeedsReview, 'next_evaluation_on' => '2026-10-01']);

    expect(attReasons($paused))->toBe([Attention::NEEDS_REVIEW]);
});

it('labels every reason in Indonesian', function () {
    foreach (array_keys(Attention::LABELS) as $reason) {
        expect(Attention::label($reason))->not->toBe($reason);
    }
});

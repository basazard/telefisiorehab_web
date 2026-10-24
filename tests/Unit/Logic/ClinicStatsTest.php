<?php

use App\Enums\ProgramStatus;
use App\Enums\RiskTier;
use App\Support\Staff\Attention;
use App\Support\Staff\ClinicStats;
use Illuminate\Support\Carbon;

// Pure figures of the clinic dashboard, from PatientOverview rows.

function statRow(array $o = []): array
{
    return $o + [
        'tier' => null, 'program_status' => null, 'adherence' => null, 'reasons' => [], 'unreviewed' => false,
        'therapist_id' => null, 'joined_at' => Carbon::parse('2026-01-01'),
    ];
}

it('counts the clinic numbers', function () {
    $today = Carbon::parse('2026-10-25');
    $rows = collect([
        statRow(['program_status' => ProgramStatus::Active, 'adherence' => 80, 'joined_at' => Carbon::parse('2026-10-20')]),
        statRow(['program_status' => ProgramStatus::Active, 'adherence' => 40, 'unreviewed' => true, 'reasons' => [Attention::EVALUATION_OVERDUE, Attention::LOW_ADHERENCE]]),
        statRow(['program_status' => ProgramStatus::NeedsReview, 'adherence' => 0, 'reasons' => [Attention::NEEDS_REVIEW]]),
        statRow(['reasons' => [Attention::RED_FLAG_LOCKED], 'joined_at' => Carbon::parse('2026-09-25')]),       // exactly 30 days ago
        statRow(['reasons' => [Attention::OPEN_CONSULTATION]]),
        statRow(['program_status' => ProgramStatus::Ended, 'joined_at' => Carbon::parse('2026-09-24')]),         // 31 days ago
    ]);

    expect(ClinicStats::summary($rows, $today))->toBe([
        'active' => 3,          // active + needs review
        'new' => 2,
        'adherence' => 40,      // (80 + 40 + 0) / 3
        'review' => 2,          // needs review + unreviewed monitored program
        'consultations' => 1,
        'locked' => 1,
        'overdue' => 1,
    ]);
});

it('has no average adherence without programs', function () {
    expect(ClinicStats::summary(collect([statRow()]))['adherence'])->toBeNull();
});

it('counts patients per risk tier, unclassified ones apart', function () {
    $rows = collect([statRow(['tier' => RiskTier::Low]), statRow(['tier' => RiskTier::Low]), statRow(['tier' => RiskTier::High]), statRow()]);

    expect(ClinicStats::tiers($rows))->toBe(['low' => 2, 'moderate' => 0, 'high' => 1, 'none' => 1]);
});

it('gives each therapist their patients, attention and adherence', function () {
    $rows = collect([
        statRow(['therapist_id' => 1, 'adherence' => 100]),
        statRow(['therapist_id' => 1, 'adherence' => 50, 'reasons' => [Attention::LOW_ADHERENCE]]),
        statRow(['therapist_id' => 2]),
        statRow(),
    ]);
    $therapists = [(object) ['id' => 1, 'name' => 'Ani'], (object) ['id' => 2, 'name' => 'Budi'], (object) ['id' => 3, 'name' => 'Citra']];

    expect(ClinicStats::workload($rows, $therapists))->toBe([
        ['id' => 1, 'name' => 'Ani', 'patients' => 2, 'attention' => 1, 'adherence' => 75],
        ['id' => 2, 'name' => 'Budi', 'patients' => 1, 'attention' => 0, 'adherence' => null],
        ['id' => 3, 'name' => 'Citra', 'patients' => 0, 'attention' => 0, 'adherence' => null],
    ]);
});

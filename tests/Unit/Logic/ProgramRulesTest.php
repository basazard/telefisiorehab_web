<?php

use App\Enums\RiskTier;
use App\Support\Staff\ProgramRules;

// Pure rules of what a physiotherapist may change in a program (PRD 09 S3.3).

it('uses the dose fields of each exercise unit', function () {
    expect(ProgramRules::fields('reps'))->toBe(['sets', 'reps'])
        ->and(ProgramRules::fields('seconds'))->toBe(['sets', 'hold_seconds'])
        ->and(ProgramRules::fields('minutes'))->toBe(['minutes']);
});

it('knows the range the system itself produces', function () {
    // Intervention::dose: sets 3 (Rendah) or 2 (Sedang); reps 10/8 + 2 per level above 1, up to level 3
    expect(ProgramRules::systemRange('reps', 'strengthening'))->toBe(['sets' => [2, 3], 'reps' => [8, 14]])
        ->and(ProgramRules::systemRange('seconds', 'balance'))->toBe(['sets' => [2, 3], 'hold_seconds' => [15, 30]])
        ->and(ProgramRules::systemRange('minutes', 'aerobic'))->toBe(['minutes' => [10, 25]]);
});

it('allows 1 up to the DRAFT hard maximum', function () {
    expect(ProgramRules::HARD_MAX)->toBe(['sets' => 5, 'reps' => 20, 'hold_seconds' => 60, 'minutes' => 45])
        ->and(ProgramRules::limits('reps', 'strengthening'))->toBe(['sets' => [1, 5], 'reps' => [1, 20]])
        ->and(ProgramRules::limits('minutes', 'aerobic'))->toBe(['minutes' => [1, 45]]);
});

it('accepts doses on the edges and refuses doses outside', function () {
    expect(ProgramRules::doseErrors('reps', 'strengthening', ['sets' => 1, 'reps' => 20]))->toBe([])
        ->and(ProgramRules::doseErrors('reps', 'strengthening', ['sets' => '5', 'reps' => '1']))->toBe([])
        ->and(array_keys(ProgramRules::doseErrors('reps', 'strengthening', ['sets' => 0, 'reps' => 21])))->toBe(['sets', 'reps'])
        ->and(array_keys(ProgramRules::doseErrors('reps', 'strengthening', ['sets' => 2.5, 'reps' => 'x'])))->toBe(['sets', 'reps'])
        ->and(array_keys(ProgramRules::doseErrors('seconds', 'stretching', ['sets' => 2])))->toBe(['hold_seconds'])
        ->and(ProgramRules::doseErrors('minutes', 'aerobic', ['minutes' => 46]))->toBe(['minutes' => 'Menit harus angka 1–45.']);
});

it('keeps only the dose fields of the unit', function () {
    expect(ProgramRules::normalizeDose('minutes', ['minutes' => '20', 'sets' => 9, 'hold_seconds' => 30]))
        ->toBe(['sets' => 1, 'reps' => 1, 'hold_seconds' => null, 'minutes' => 20])
        ->and(ProgramRules::normalizeDose('seconds', ['sets' => '2', 'hold_seconds' => '25', 'reps' => 8]))
        ->toBe(['sets' => 2, 'reps' => 1, 'hold_seconds' => 25, 'minutes' => null]);
});

it('needs a note of at least 10 characters', function () {
    expect(ProgramRules::noteError('Dosis turun'))->toBeNull()
        ->and(ProgramRules::noteError('   pendek   '))->not->toBeNull()
        ->and(ProgramRules::noteError(null))->not->toBeNull();
});

it('refuses a new program when the patient is locked, high risk, not assessed or already has one', function () {
    expect(ProgramRules::createRefusal(true, RiskTier::Low, false))->toContain('red flag')
        ->and(ProgramRules::createRefusal(false, null, false))->toContain('assessment')
        ->and(ProgramRules::createRefusal(false, RiskTier::High, false))->toContain('Risiko Tinggi')
        ->and(ProgramRules::createRefusal(false, RiskTier::Moderate, true))->toContain('sudah memiliki program')
        ->and(ProgramRules::createRefusal(false, RiskTier::Moderate, false))->toBeNull()
        ->and(ProgramRules::createRefusal(false, RiskTier::Low, false))->toBeNull();
});

it('uses a DRAFT 2-week evaluation for programs a physiotherapist creates', function () {
    expect(ProgramRules::CREATED_EVALUATION_WEEKS)->toBe(2);
});

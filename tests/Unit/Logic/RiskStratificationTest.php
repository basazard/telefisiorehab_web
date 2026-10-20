<?php

use App\Enums\RiskTier;
use App\Support\Onboarding\ClinicalAssessment;
use App\Support\Onboarding\RiskStratification;

function bandOf(string $key, array $answers, int $pain = 0): string
{
    return RiskStratification::stratify([$key], [$key => $answers], $pain)['items'][$key]['tier'];
}

function lefsOf(int $total): array
{
    $items = array_fill(0, 20, 0);
    for ($i = 0; $total > 0; $i++, $total -= 4) {
        $items[$i] = min(4, $total);
    }

    return $items;
}

// ---------------------------------------------------------------- per instrument

it('bands pain as in the PRD matrix', function () {
    $tier = fn (int $pain) => RiskStratification::stratify([], [], $pain)['items']['nrs']['tier'];

    expect([$tier(0), $tier(3), $tier(4), $tier(6), $tier(7), $tier(10)])
        ->toBe(['low', 'low', 'moderate', 'moderate', 'high', 'high']);
});

it('bands the function questionnaires', function () {
    expect([bandOf('lefs', lefsOf(60)), bandOf('lefs', lefsOf(59)), bandOf('lefs', lefsOf(30)), bandOf('lefs', lefsOf(29))])
        ->toBe(['low', 'moderate', 'moderate', 'high'])
        ->and([bandOf('odi', array_fill(0, 10, 1)), bandOf('odi', array_fill(0, 10, 2)), bandOf('odi', [3, ...array_fill(0, 9, 2)])])
        ->toBe(['low', 'moderate', 'high'])                                      // 20 %, 40 %, 42 %
        ->and([bandOf('spadi', array_fill(0, 13, 2)), bandOf('spadi', array_fill(0, 13, 5)), bandOf('spadi', array_fill(0, 13, 6))])
        ->toBe(['low', 'moderate', 'high'])                                      // 20 %, 50 %, 60 %
        ->and([bandOf('psfs', [['activity' => 'x', 'score' => 7]]), bandOf('psfs', [['activity' => 'x', 'score' => 4]]), bandOf('psfs', [['activity' => 'x', 'score' => 3]])])
        ->toBe(['low', 'moderate', 'high']);
});

it('bands ADL and fall risk', function () {
    $barthel = fn (int $stairs, int $mobility) => ['feeding' => 10, 'bathing' => 5, 'grooming' => 5, 'dressing' => 10, 'bowels' => 10,
        'bladder' => 10, 'toilet' => 10, 'transfer' => 15, 'mobility' => $mobility, 'stairs' => $stairs];
    $steadi = fn (int $yes) => array_merge(array_fill(0, $yes, 'yes'), array_fill(0, 12 - $yes, 'no'));

    expect([bandOf('barthel', $barthel(10, 15)), bandOf('barthel', $barthel(5, 15))])->toBe(['low', 'moderate'])
        ->and(bandOf('barthel', ['feeding' => 5, 'bathing' => 0, 'grooming' => 0, 'dressing' => 5, 'bowels' => 10,
            'bladder' => 10, 'toilet' => 5, 'transfer' => 10, 'mobility' => 10, 'stairs' => 5]))->toBe('high')    // 60
        ->and([bandOf('steadi', $steadi(1)), bandOf('steadi', $steadi(2)), bandOf('steadi', $steadi(5))])
        ->toBe(['low', 'moderate', 'high'])                                      // 2, 4, 7 points
        ->and([bandOf('tug', ['unable' => false, 'seconds' => 11]), bandOf('tug', ['unable' => false, 'seconds' => 19.9]),
            bandOf('tug', ['unable' => false, 'seconds' => 20]), bandOf('tug', ['unable' => true, 'seconds' => null])])
        ->toBe(['low', 'moderate', 'high', 'high']);
});

it('bands the cardiopulmonary instruments', function () {
    expect([bandOf('mmrc', ['grade' => 1]), bandOf('mmrc', ['grade' => 2]), bandOf('mmrc', ['grade' => 3])])->toBe(['low', 'moderate', 'high'])
        ->and([
            bandOf('sts', ['unable' => false, 'reps' => 20, 'borg' => 4]),
            bandOf('sts', ['unable' => false, 'reps' => 12, 'borg' => 4]),
            bandOf('sts', ['unable' => false, 'reps' => 11, 'borg' => 4]),
            bandOf('sts', ['unable' => false, 'reps' => 25, 'borg' => 7]),
            bandOf('sts', ['unable' => true, 'reps' => null, 'borg' => null]),
        ])->toBe(['low', 'moderate', 'high', 'high', 'high'])
        ->and([
            bandOf('vitals', ['spo2' => null, 'hr' => null, 'rr' => null]),
            bandOf('vitals', ['spo2' => 97, 'hr' => 80, 'rr' => 16]),
            bandOf('vitals', ['spo2' => 92, 'hr' => '', 'rr' => '']),
            bandOf('vitals', ['spo2' => null, 'hr' => 121, 'rr' => null]),
            bandOf('vitals', ['spo2' => null, 'hr' => null, 'rr' => 25]),
        ])->toBe(['low', 'low', 'moderate', 'high', 'high']);
});

it('bands edema and wound status', function () {
    expect([
        bandOf('edema', ['level' => 'mild', 'affected_cm' => null, 'healthy_cm' => null]),
        bandOf('edema', ['level' => 'moderate', 'affected_cm' => null, 'healthy_cm' => null]),
        bandOf('edema', ['level' => 'mild', 'affected_cm' => 40, 'healthy_cm' => 36]),
        bandOf('edema', ['level' => 'severe', 'affected_cm' => null, 'healthy_cm' => null]),
        bandOf('wound', ['status' => 'clean']),
        bandOf('wound', ['status' => 'infection']),
    ])->toBe(['low', 'moderate', 'high', 'high', 'low', 'high']);
});

// ---------------------------------------------------------------- overall category

it('takes the highest band as the overall category', function () {
    $result = RiskStratification::stratify(['mmrc', 'tug'], [
        'mmrc' => ['grade' => 2],
        'tug' => ['unable' => false, 'seconds' => 25],
    ], 1);

    expect($result['tier'])->toBe(RiskTier::High)
        ->and(RiskStratification::stratify(['mmrc'], ['mmrc' => ['grade' => 0]], 5)['tier'])->toBe(RiskTier::Moderate)
        ->and(RiskStratification::stratify(['mmrc'], ['mmrc' => ['grade' => 0]], 2)['tier'])->toBe(RiskTier::Low);
});

it('never rates a pediatric assessment without a physiotherapist as low', function () {
    $result = RiskStratification::stratify([], [], 0);

    expect($result['tier'])->toBe(RiskTier::Moderate)->and($result['items'])->toHaveKey('clinician');
});

it('agrees with the Clinical Assessment branch: no limitation means Risiko Rendah', function () {
    $cases = [
        [['lefs'], ['lefs' => lefsOf(80)], 3],
        [['lefs'], ['lefs' => lefsOf(59)], 0],
        [['mmrc', 'sts', 'vitals'], ['mmrc' => ['grade' => 1], 'sts' => ['unable' => false, 'reps' => 20, 'borg' => 4], 'vitals' => ['spo2' => 95, 'hr' => 100, 'rr' => 20]], 0],
        [['mmrc', 'sts', 'vitals'], ['mmrc' => ['grade' => 1], 'sts' => ['unable' => false, 'reps' => 20, 'borg' => 4], 'vitals' => ['spo2' => 94, 'hr' => 100, 'rr' => 20]], 0],
        [['edema', 'wound'], ['edema' => ['level' => 'mild', 'affected_cm' => 37.9, 'healthy_cm' => 36], 'wound' => ['status' => 'clean']], 3],
        [['edema', 'wound'], ['edema' => ['level' => 'mild', 'affected_cm' => 38, 'healthy_cm' => 36], 'wound' => ['status' => 'clean']], 3],
        [['psfs'], ['psfs' => [['activity' => 'x', 'score' => 7]]], 4],
    ];

    foreach ($cases as [$instruments, $answers, $pain]) {
        $limited = ClinicalAssessment::analyse($instruments, $answers, $pain)['has_limitation'];
        $tier = RiskStratification::stratify($instruments, $answers, $pain)['tier'];

        expect($tier === RiskTier::Low)->toBe(! $limited);
    }
});

// ---------------------------------------------------------------- emergency escalation

it('flags SpO2 below 90 % and wound infection as emergencies', function () {
    expect(RiskStratification::stratify(['vitals'], ['vitals' => ['spo2' => 90, 'hr' => null, 'rr' => null]], 0)['emergencies'])->toBe([])
        ->and(RiskStratification::stratify(['vitals'], ['vitals' => ['spo2' => 89, 'hr' => null, 'rr' => null]], 0)['emergencies'])->toBe(['spo2_critical'])
        ->and(RiskStratification::stratify(['wound'], ['wound' => ['status' => 'infection']], 0)['emergencies'])->toBe(['wound_infection']);
});

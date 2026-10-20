<?php

use App\Enums\RiskTier;
use App\Support\Onboarding\Monitoring;

function lefsTotal(int $total): array
{
    $items = array_fill(0, 20, 0);
    for ($i = 0; $total > 0; $i++, $total -= 4) {
        $items[$i] = min(4, $total);
    }

    return $items;
}

function decideWith(array $comparison, array $o = []): array
{
    $o += ['pain' => 3, 'global' => 'same', 'new' => false, 'side' => false, 'tier' => RiskTier::Moderate, 'emergency' => false, 'weeks' => 1, 'maintenance' => false];

    return Monitoring::decide($comparison, $o['pain'], $o['global'], $o['new'], $o['side'], $o['tier'], $o['emergency'], $o['weeks'], $o['maintenance']);
}

// ---------------------------------------------------------------- automated progress comparison

it('compares every comparable score with the baseline using its MCID', function () {
    $c = Monitoring::compare(['lefs', 'odi', 'vitals'],
        ['lefs' => lefsTotal(40), 'odi' => array_fill(0, 10, 3), 'vitals' => ['spo2' => 97, 'hr' => null, 'rr' => null]],
        ['lefs' => lefsTotal(49), 'odi' => array_fill(0, 10, 2), 'vitals' => ['spo2' => 97, 'hr' => null, 'rr' => null]],
        6, 5);

    expect(array_keys($c))->toBe(['nrs', 'lefs', 'odi'])                    // vitals feed the risk category, not the comparison
        ->and($c['nrs'])->toMatchArray(['before' => '6/10', 'after' => '5/10', 'change' => -1.0, 'direction' => 'improved', 'significant' => false])
        ->and($c['lefs'])->toMatchArray(['change' => 9.0, 'direction' => 'improved', 'significant' => true])
        ->and($c['odi'])->toMatchArray(['before' => '60%', 'after' => '40%', 'change' => -20.0, 'direction' => 'improved', 'significant' => true]);
});

it('treats "could not do the test" as the worst value', function () {
    $c = Monitoring::compare(['tug', 'sts'],
        ['tug' => ['unable' => true, 'seconds' => null], 'sts' => ['unable' => false, 'reps' => 15, 'borg' => 3]],
        ['tug' => ['unable' => false, 'seconds' => 14], 'sts' => ['unable' => true, 'reps' => null, 'borg' => null]],
        2, 2);

    expect($c['tug'])->toMatchArray(['direction' => 'improved', 'significant' => true])
        ->and($c['sts'])->toMatchArray(['direction' => 'worse', 'significant' => true])
        ->and($c['nrs'])->toMatchArray(['direction' => 'same', 'significant' => false]);
});

it('compares edema by level', function () {
    $c = Monitoring::compare(['edema'],
        ['edema' => ['level' => 'moderate', 'affected_cm' => null, 'healthy_cm' => null]],
        ['edema' => ['level' => 'mild', 'affected_cm' => null, 'healthy_cm' => null]], 2, 2);

    expect($c['edema'])->toMatchArray(['change' => -1.0, 'direction' => 'improved', 'significant' => true]);
});

// ---------------------------------------------------------------- adaptive branching

it('continues progressively on a significant improvement', function () {
    $c = Monitoring::compare(['lefs'], ['lefs' => lefsTotal(40)], ['lefs' => lefsTotal(50)], 5, 4);

    expect(decideWith($c))->toMatchArray(['significant' => true, 'outcome' => Monitoring::OUTCOME_PROGRESS, 'goal_achieved' => false]);
});

it('modifies the program when there is no significant change yet', function () {
    $c = Monitoring::compare(['lefs'], ['lefs' => lefsTotal(40)], ['lefs' => lefsTotal(44)], 5, 4);

    expect(decideWith($c)['outcome'])->toBe(Monitoring::OUTCOME_MODIFY)
        ->and(decideWith($c, ['weeks' => 4])['outcome'])->toBe(Monitoring::OUTCOME_REASSESS);   // 4+ weeks without improvement
});

it('reassesses when something got worse, even next to an improvement', function () {
    $better = Monitoring::compare(['lefs'], ['lefs' => lefsTotal(40)], ['lefs' => lefsTotal(60)], 5, 3);

    expect(decideWith($better, ['new' => true])['outcome'])->toBe(Monitoring::OUTCOME_REASSESS)
        ->and(decideWith($better, ['side' => true])['outcome'])->toBe(Monitoring::OUTCOME_REASSESS)
        ->and(decideWith($better, ['global' => 'worse'])['outcome'])->toBe(Monitoring::OUTCOME_REASSESS)
        ->and(decideWith($better, ['global' => 'worse'])['significant'])->toBeFalse();
});

it('advises referral on drastic worsening', function () {
    $painUp = Monitoring::compare([], [], [], 3, 6);
    $same = Monitoring::compare([], [], [], 3, 3);

    expect(decideWith($painUp, ['pain' => 6])['outcome'])->toBe(Monitoring::OUTCOME_REFERRAL)
        ->and(decideWith($same, ['tier' => RiskTier::High])['outcome'])->toBe(Monitoring::OUTCOME_REFERRAL)
        ->and(decideWith($same, ['emergency' => true])['outcome'])->toBe(Monitoring::OUTCOME_REFERRAL);
});

// ---------------------------------------------------------------- goal achieved

it('marks the goal achieved at Risiko Rendah with little pain and nothing worse', function () {
    $c = Monitoring::compare(['lefs'], ['lefs' => lefsTotal(55)], ['lefs' => lefsTotal(70)], 4, 1);

    expect(decideWith($c, ['pain' => 1, 'tier' => RiskTier::Low]))->toMatchArray(['goal_achieved' => true, 'outcome' => Monitoring::OUTCOME_MAINTENANCE])
        ->and(decideWith($c, ['pain' => 3, 'tier' => RiskTier::Low])['goal_achieved'])->toBeFalse()
        ->and(decideWith($c, ['pain' => 1, 'tier' => RiskTier::Moderate])['goal_achieved'])->toBeFalse()
        ->and(decideWith($c, ['pain' => 1, 'tier' => RiskTier::Low, 'new' => true])['goal_achieved'])->toBeFalse()
        ->and(decideWith($c, ['pain' => 1, 'tier' => RiskTier::Low, 'maintenance' => true])['outcome'])->toBe(Monitoring::OUTCOME_PROGRESS);
});

<?php

use App\Support\Onboarding\Assessment;

it('derives age from a date of birth', function () {
    $now = new DateTimeImmutable('2026-10-17');

    expect(Assessment::age('2000-10-17', $now))->toBe(26)
        ->and(Assessment::age('2000-10-18', $now))->toBe(25)
        ->and(Assessment::age('2026-10-17', $now))->toBe(0)
        ->and(Assessment::age('', $now))->toBeNull()
        ->and(Assessment::age(null, $now))->toBeNull()
        ->and(Assessment::age('not-a-date', $now))->toBeNull()
        ->and(Assessment::age('2030-01-01', $now))->toBeNull()   // future
        ->and(Assessment::age('1800-01-01', $now))->toBeNull();  // 120 or older
});

it('maps age to the three tracks', function () {
    expect(Assessment::track(0))->toBe('pediatric')
        ->and(Assessment::track(17))->toBe('pediatric')
        ->and(Assessment::track(18))->toBe('adult')
        ->and(Assessment::track(59))->toBe('adult')
        ->and(Assessment::track(60))->toBe('geriatric')
        ->and(Assessment::track(null))->toBeNull();
});

it('computes BMI and its category', function () {
    expect(Assessment::bmi(170, 70))->toBe(['value' => 24.2, 'category' => 'Normal', 'tone' => 'success'])
        ->and(Assessment::bmi(160, 45))->toBe(['value' => 17.6, 'category' => 'Underweight', 'tone' => 'destructive'])
        ->and(Assessment::bmi(170, 80))->toBe(['value' => 27.7, 'category' => 'Overweight', 'tone' => 'warning'])
        ->and(Assessment::bmi(170, 95))->toBe(['value' => 32.9, 'category' => 'Obese', 'tone' => 'destructive']);
});

it('accepts numeric strings for BMI', function () {
    expect(Assessment::bmi('170', '70')['value'])->toBe(24.2);
});

it('returns no BMI for missing or impossible values', function () {
    expect(Assessment::bmi('', 70))->toBeNull()
        ->and(Assessment::bmi(170, ''))->toBeNull()
        ->and(Assessment::bmi(null, null))->toBeNull()
        ->and(Assessment::bmi(0, 70))->toBeNull()
        ->and(Assessment::bmi(100, 95))->toBeNull();   // BMI 95 is above the accepted maximum of 90
});

it('labels the 0-10 pain scale', function () {
    $expected = [0 => 'No Pain', 1 => 'Mild Pain', 3 => 'Mild Pain', 4 => 'Moderate Pain', 6 => 'Moderate Pain',
        7 => 'Severe Pain', 8 => 'Severe Pain', 9 => 'Worst Imaginable', 10 => 'Worst Imaginable'];

    foreach ($expected as $value => $label) {
        expect(Assessment::nrsLabel($value))->toBe($label);
    }
});

it('takes the highest pain level', function () {
    expect(Assessment::maxPain(['a' => ['nrs' => 3], 'b' => ['nrs' => 7]]))->toBe(7)
        ->and(Assessment::maxPain([]))->toBe(0);
});

it('flags the ODI questionnaire for any region containing back (known quirk)', function () {
    expect(Assessment::needsOdi(['Lower Back']))->toBeTrue()
        ->and(Assessment::needsOdi(['Upper Back']))->toBeTrue()
        ->and(Assessment::needsOdi(['Back of Head']))->toBeTrue()
        ->and(Assessment::needsOdi(['Left Knee', 'Chest']))->toBeFalse()
        ->and(Assessment::needsOdi([]))->toBeFalse();
});

it('picks the clinical domain in the legacy priority order (parked until the domain PRD)', function () {
    expect(Assessment::pickDomain(['Neurological', 'Cardiac'], [], 'adult', []))->toBe('Neuromuscular')
        ->and(Assessment::pickDomain(['Cardiac'], [], 'adult', []))->toBe('Cardiopulmonary')
        ->and(Assessment::pickDomain([], ['Chest'], 'adult', []))->toBe('Cardiopulmonary')
        ->and(Assessment::pickDomain(['Post-surgery'], [], 'adult', []))->toBe('Post-op/Injury')
        ->and(Assessment::pickDomain([], [], 'pediatric', []))->toBe('Pediatric Development')
        ->and(Assessment::pickDomain([], [], 'geriatric', []))->toBe('Geriatric/Fall Risk')
        ->and(Assessment::pickDomain([], [], 'adult', ['Return to sport']))->toBe('Sports Performance')
        ->and(Assessment::pickDomain([], [], 'adult', []))->toBe('Musculoskeletal');
});

function lefsAnswers(int $each): array
{
    return array_fill_keys(array_map(fn ($i) => "lefs-$i", range(0, 19)), $each);
}

function odiAnswers(int $each): array
{
    return array_fill_keys(array_map(fn ($i) => "odi-$i", range(0, 9)), $each);
}

it('scores the LEFS as the sum of 20 items', function () {
    expect(Assessment::score(lefsAnswers(4), [], [])['lefs'])->toBe(80)
        ->and(Assessment::score(lefsAnswers(2), [], [])['lefs'])->toBe(40)
        ->and(Assessment::score([], [], [])['lefs'])->toBe(0);
});

it('assigns the low tier only with good function and little pain', function () {
    expect(Assessment::score(lefsAnswers(4), [], ['Left Knee' => ['nrs' => 2]])['tier'])->toBe('low')
        ->and(Assessment::score(lefsAnswers(3), [], ['Left Knee' => ['nrs' => 3]])['tier'])->toBe('low');   // LEFS 60, pain 3
});

it('assigns moderate when function AND pain are both in the middle band (the && rule)', function () {
    expect(Assessment::score(lefsAnswers(2), [], ['Left Knee' => ['nrs' => 4]])['tier'])->toBe('moderate')  // LEFS 40, pain 4
        ->and(Assessment::score(lefsAnswers(3), [], ['Left Knee' => ['nrs' => 5]])['tier'])->toBe('moderate'); // LEFS 60, pain 5
});

it('assigns high when function is very low even with moderate pain', function () {
    $answers = lefsAnswers(0);
    $answers['lefs-0'] = 4;   // LEFS 4/80

    expect(Assessment::score($answers, [], ['Left Knee' => ['nrs' => 4]])['tier'])->toBe('high');
});

it('assigns high when pain is above 6', function () {
    expect(Assessment::score(lefsAnswers(4), [], ['Left Knee' => ['nrs' => 8]])['tier'])->toBe('high');
});

it('only computes the ODI when a back region is selected', function () {
    $without = Assessment::score(lefsAnswers(4) + odiAnswers(4), ['Left Knee'], []);
    expect($without['odi'])->toBeNull()->and($without['odiPercent'])->toBeNull();

    $with = Assessment::score(lefsAnswers(4) + odiAnswers(2), ['Lower Back'], []);
    expect($with['odi'])->toBe(20)->and($with['odiPercent'])->toBe(40);
});

it('forces the high tier when ODI is at least 60 percent', function () {
    $result = Assessment::score(lefsAnswers(4) + odiAnswers(4), ['Lower Back'], ['Lower Back' => ['nrs' => 1]]);

    expect($result['odiPercent'])->toBe(80)->and($result['tier'])->toBe('high');
});

it('passes ROM and pain through', function () {
    $result = Assessment::score(lefsAnswers(4) + ['rom' => 45], [], ['A' => ['nrs' => 2]]);

    expect($result['rom'])->toBe(45)->and($result['pain'])->toBe(2);
});

it('returns the tier description', function () {
    $info = Assessment::tierInfo('moderate');

    expect($info)->toHaveKey('label')->and($info)->toHaveKey('program');
});

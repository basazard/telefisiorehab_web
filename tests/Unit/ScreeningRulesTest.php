<?php

use App\Support\Onboarding\ScreeningRules;

it('lists the nine PRD complaints', function () {
    expect(ScreeningRules::COMPLAINTS)->toHaveCount(9)
        ->and(array_key_exists('other', ScreeningRules::COMPLAINTS))->toBeTrue();
});

it('requires a body area only for localised complaints', function () {
    expect(ScreeningRules::locationRequired(['pain']))->toBeTrue()
        ->and(ScreeningRules::locationRequired(['balance', 'walking', 'breathless']))->toBeFalse()
        ->and(ScreeningRules::locationRequired(['balance', 'stiffness']))->toBeTrue()
        ->and(ScreeningRules::locationRequired([]))->toBeFalse()
        ->and(ScreeningRules::locationRequired(['other']))->toBeFalse();
});

it('shows extra questions based on the chosen complaints', function () {
    $q = ScreeningRules::questions(['pain']);
    expect($q['painLevel'])->toBeTrue()
        ->and($q['radiating'])->toBeTrue()
        ->and($q['sleepDisturbance'])->toBeTrue()
        ->and($q['painCharacter'])->toBeTrue()
        ->and($q['stiffness'])->toBeFalse()
        ->and($q['tingling'])->toBeFalse();

    expect(ScreeningRules::questions(['weakness'])['tingling'])->toBeTrue();
    expect(ScreeningRules::questions(['stiffness'])['stiffness'])->toBeTrue();
    expect(array_filter(ScreeningRules::questions(['breathless'])))->toBe([]);
    expect(array_filter(ScreeningRules::questions([])))->toBe([]);
});

it('combines the questions of several complaints', function () {
    $q = ScreeningRules::questions(['pain', 'stiffness', 'weakness']);
    expect($q['painLevel'])->toBeTrue()->and($q['stiffness'])->toBeTrue()->and($q['tingling'])->toBeTrue();
});

it('returns every special group that applies, in order', function () {
    expect(ScreeningRules::specialGroups(65, ['post_surgery'], ['pain'], 'male'))->toBe(['geriatric', 'post_op_injury'])
        ->and(ScreeningRules::specialGroups(30, ['pregnancy'], [], 'female'))->toBe(['pregnancy'])
        ->and(ScreeningRules::specialGroups(10, [], [], 'male'))->toBe(['pediatric'])
        ->and(ScreeningRules::specialGroups(40, [], [], 'male'))->toBe(['other'])
        ->and(ScreeningRules::specialGroups(40, [], ['post_injury'], 'male'))->toBe(['post_op_injury'])
        ->and(ScreeningRules::specialGroups(40, ['injury'], [], 'male'))->toBe(['post_op_injury']);
});

it('uses the age boundaries 18 and 60', function () {
    expect(ScreeningRules::specialGroups(17, [], [], 'male'))->toBe(['pediatric'])
        ->and(ScreeningRules::specialGroups(18, [], [], 'male'))->toBe(['other'])
        ->and(ScreeningRules::specialGroups(59, [], [], 'male'))->toBe(['other'])
        ->and(ScreeningRules::specialGroups(60, [], [], 'male'))->toBe(['geriatric']);
});

it('ignores pregnancy for non-female profiles', function () {
    expect(ScreeningRules::specialGroups(30, ['pregnancy'], [], 'male'))->toBe(['other'])
        ->and(ScreeningRules::specialGroups(30, ['pregnancy'], [], null))->toBe(['other']);
});

it('handles a profile without an age', function () {
    expect(ScreeningRules::specialGroups(null, [], [], 'female'))->toBe(['other']);
});

it('has a label for every group', function () {
    foreach (['pediatric', 'pregnancy', 'geriatric', 'post_op_injury', 'other'] as $key) {
        expect(ScreeningRules::GROUPS[$key] ?? '')->not->toBeEmpty();
    }
});

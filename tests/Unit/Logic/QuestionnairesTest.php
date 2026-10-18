<?php

use App\Support\Onboarding\Questionnaires as Q;

it('keeps the legacy list sizes (parked data used by the assessment)', function () {
    expect(Q::CONDITIONS)->toHaveCount(6)
        ->and(Q::GOALS)->toHaveCount(7)
        ->and(Q::OCCUPATIONS)->toHaveCount(9)
        ->and(Q::ACTIVITY_LEVELS)->toHaveCount(5)
        ->and(Q::PAIN_CHARACTERS)->toHaveCount(7)
        ->and(Q::AGGRAVATORS)->toHaveCount(6)
        ->and(Q::RED_FLAG_QUESTIONS)->toHaveCount(14)
        ->and(Q::LEFS_ITEMS)->toHaveCount(20)
        ->and(Q::ODI_ITEMS)->toHaveCount(10);
});

it('has answer choices from 0', function () {
    expect(array_column(Q::LEFS_CHOICES, 'v'))->toBe([0, 1, 2, 3, 4])
        ->and(array_column(Q::ODI_CHOICES, 'v'))->toBe([0, 1, 2, 3, 4, 5]);
});

it('has a full description for each tier', function () {
    foreach (['low', 'moderate', 'high'] as $tier) {
        $info = Q::TIERS[$tier];

        expect($info['emoji'])->not->toBeEmpty()
            ->and($info['label'])->not->toBeEmpty()
            ->and($info['meaning'])->not->toBeEmpty()
            ->and($info['program'])->not->toBeEmpty();
    }
});

it('has labels for the three tracks', function () {
    expect(array_keys(Q::TRACK_LABELS))->toBe(['pediatric', 'adult', 'geriatric']);
});

it('contains no empty questions', function () {
    foreach ([Q::RED_FLAG_QUESTIONS, Q::LEFS_ITEMS, Q::ODI_ITEMS] as $list) {
        foreach ($list as $text) {
            expect(trim($text))->not->toBeEmpty();
        }
    }
});

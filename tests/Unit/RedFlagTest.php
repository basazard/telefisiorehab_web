<?php

use App\Support\Onboarding\RedFlags;

function allNo(): array
{
    return array_fill_keys(RedFlags::keys(), 'no');
}

it('has 16 questions, 9 of them from the PRD', function () {
    expect(RedFlags::ITEMS)->toHaveCount(16)
        ->and(RedFlags::FROM_PRD)->toHaveCount(9);

    foreach (RedFlags::FROM_PRD as $key) {
        expect(array_key_exists($key, RedFlags::ITEMS))->toBeTrue();
    }
});

it('has no stop when every answer is no', function () {
    expect(RedFlags::evaluate(allNo()))->toBe(['flagged' => [], 'stop' => false]);
});

it('stops on a single yes and lists which one', function () {
    $answers = allNo();
    $answers['chest_pain'] = 'yes';
    $answers['night_pain'] = 'yes';

    expect(RedFlags::evaluate($answers))->toBe(['flagged' => ['chest_pain', 'night_pain'], 'stop' => true]);
});

it('stops on every single question when it is the only yes', function () {
    foreach (RedFlags::keys() as $key) {
        $answers = allNo();
        $answers[$key] = 'yes';

        expect(RedFlags::evaluate($answers)['flagged'])->toBe([$key]);
    }
});

it('ignores unknown keys and treats missing answers as not flagged', function () {
    expect(RedFlags::evaluate(['made_up' => 'yes']))->toBe(['flagged' => [], 'stop' => false]);
});

it('only counts the exact string yes', function () {
    expect(RedFlags::evaluate(['chest_pain' => true, 'fainting' => 'YES', 'night_pain' => 1])['stop'])->toBeFalse();
});

it('requires every question to be answered', function () {
    $answers = allNo();
    expect(RedFlags::allAnswered($answers))->toBeTrue();

    unset($answers['fainting']);
    expect(RedFlags::allAnswered($answers))->toBeFalse();

    $answers['fainting'] = 'maybe';
    expect(RedFlags::allAnswered($answers))->toBeFalse();
});

it('stops automatically at a high pain score, even with every answer no', function () {
    expect(RedFlags::PAIN_STOP_THRESHOLD)->toBe(9)
        ->and(RedFlags::evaluate(allNo(), 8))->toBe(['flagged' => [], 'stop' => false])
        ->and(RedFlags::evaluate(allNo(), 9))->toBe(['flagged' => ['pain_score_high'], 'stop' => true])
        ->and(RedFlags::evaluate(allNo(), 10)['stop'])->toBeTrue();
});

it('reports both the explicit answer and the pain score when both apply', function () {
    $answers = allNo();
    $answers['severe_pain'] = 'yes';

    expect(RedFlags::evaluate($answers, 10)['flagged'])->toBe(['severe_pain', 'pain_score_high']);
});

it('turns keys into readable labels without the question mark', function () {
    expect(RedFlags::labels(['chest_pain']))->toBe(['Nyeri dada atau dada terasa tertekan'])
        ->and(RedFlags::labels(['pain_score_high']))->toBe(['Skor nyeri sangat tinggi (9–10 dari 10)'])
        ->and(RedFlags::labels(['unknown_key']))->toBe(['unknown_key']);
});

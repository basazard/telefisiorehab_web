<?php

use App\Enums\ExerciseType;
use App\Enums\PhysioDomain;
use App\Enums\RiskTier;
use App\Support\Onboarding\ExerciseLibrary;
use App\Support\Onboarding\Intervention;

function library(): array
{
    $items = [];
    foreach (ExerciseLibrary::ITEMS as $slug => [, $type, $area]) {
        $items[] = ['slug' => $slug, 'type' => $type, 'body_area' => $area];
    }

    return $items;
}

// ---------------------------------------------------------------- path routing

it('routes each risk category to its pathway', function () {
    expect(Intervention::pathway(RiskTier::Low)['key'])->toBe('self_management')
        ->and(Intervention::pathway(RiskTier::Moderate)['key'])->toBe('guided')
        ->and(Intervention::pathway(RiskTier::High)['key'])->toBe('consultation');
});

it('allows the PRD exercise types per category, none for Risiko Tinggi', function () {
    expect(Intervention::exerciseTypes(RiskTier::Low))
        ->toBe([ExerciseType::Stretching, ExerciseType::Strengthening, ExerciseType::Aerobic, ExerciseType::Relaxation])
        ->and(Intervention::exerciseTypes(RiskTier::Moderate, PhysioDomain::Musculoskeletal, ['low_back']))
        ->toBe([ExerciseType::Rom, ExerciseType::Strengthening])
        ->and(Intervention::exerciseTypes(RiskTier::Moderate, PhysioDomain::Geriatric))
        ->toBe([ExerciseType::Breathing, ExerciseType::Rom, ExerciseType::Strengthening, ExerciseType::Balance])
        ->and(Intervention::exerciseTypes(RiskTier::High, PhysioDomain::Musculoskeletal, ['lower_limb']))->toBe([]);
});

it('never allows self-exercise for Risiko Tinggi or pediatric patients', function () {
    expect(Intervention::canSelfExercise(RiskTier::Low, PhysioDomain::Musculoskeletal))->toBeTrue()
        ->and(Intervention::canSelfExercise(RiskTier::Moderate, PhysioDomain::Geriatric))->toBeTrue()
        ->and(Intervention::canSelfExercise(RiskTier::High, PhysioDomain::Musculoskeletal))->toBeFalse()
        ->and(Intervention::canSelfExercise(RiskTier::Moderate, PhysioDomain::Pediatric))->toBeFalse();
});

// ---------------------------------------------------------------- personalisation

it('maps screening regions to body areas', function () {
    expect(Intervention::areas(['Left Knee', 'Lower Back']))->toBe(['lower_limb', 'low_back'])
        ->and(Intervention::areas(['Right Shoulder', 'Neck']))->toBe(['shoulder', 'neck'])
        ->and(Intervention::areas([]))->toBe([]);
});

it('picks area-specific exercises first, general ones otherwise', function () {
    $knee = Intervention::select([ExerciseType::Stretching, ExerciseType::Aerobic], ['lower_limb'], library());
    $none = Intervention::select([ExerciseType::Stretching], [], library());

    expect($knee)->toBe(['hamstring-stretch', 'calf-stretch', 'easy-walk', 'marching'])
        ->and($none)->toBe(['overhead-reach']);
});

it('has at least one exercise for every type the rules can ask for', function () {
    $types = array_unique(array_column(library(), 'type'));

    foreach (ExerciseType::cases() as $type) {
        expect($types)->toContain($type->value);
    }
});

it('doses lower for Risiko Sedang and progresses by level', function () {
    expect(Intervention::dose(RiskTier::Low, 'reps', 'strengthening', 1))->toBe(['sets' => 3, 'reps' => 10, 'hold_seconds' => null, 'minutes' => null])
        ->and(Intervention::dose(RiskTier::Moderate, 'reps', 'strengthening', 1)['reps'])->toBe(8)
        ->and(Intervention::dose(RiskTier::Moderate, 'reps', 'strengthening', 3)['reps'])->toBe(12)
        ->and(Intervention::dose(RiskTier::Moderate, 'reps', 'strengthening', 9)['reps'])->toBe(12)          // capped
        ->and(Intervention::dose(RiskTier::Low, 'minutes', 'aerobic', 2)['minutes'])->toBe(20)
        ->and(Intervention::dose(RiskTier::Low, 'minutes', 'relaxation', 3)['minutes'])->toBe(10)
        ->and(Intervention::dose(RiskTier::Moderate, 'seconds', 'balance', 2)['hold_seconds'])->toBe(20);
});

// ---------------------------------------------------------------- monitoring & evaluation

it('decides "Apakah ada perbaikan?"', function () {
    $now = fn (array $o = []) => $o + ['pain' => 3, 'function' => 6, 'mobility' => 6, 'global_change' => 'same', 'new_complaint' => false];

    expect(Intervention::improved($now(['global_change' => 'better']), ['pain' => 3]))->toBeTrue()
        ->and(Intervention::improved($now(), ['pain' => 5]))->toBeTrue()                                   // pain -2
        ->and(Intervention::improved($now(), ['pain' => 4]))->toBeFalse()                                  // pain -1 only
        ->and(Intervention::improved($now(), ['pain' => 3, 'function' => 4, 'mobility' => 6]))->toBeTrue()
        ->and(Intervention::improved($now(['global_change' => 'better', 'new_complaint' => true]), ['pain' => 6]))->toBeFalse()
        ->and(Intervention::improved($now(['global_change' => 'worse']), ['pain' => 8]))->toBeFalse()
        ->and(Intervention::improved($now(['global_change' => 'better', 'pain' => 6]), ['pain' => 3]))->toBeFalse()   // pain +3
        ->and(Intervention::improved($now(['global_change' => 'better']), ['pain' => 3, 'function' => 9, 'mobility' => 6]))->toBeFalse();
});

it('computes adherence as the share of days with a logged exercise', function () {
    $from = new DateTimeImmutable('2026-10-01 08:00');
    $to = new DateTimeImmutable('2026-10-10 20:00');                                                       // 10 days

    expect(Intervention::adherence(['2026-10-01 09:00', '2026-10-01 10:00', '2026-10-03 09:00', '2026-09-30 09:00'], $from, $to))->toBe(20)
        ->and(Intervention::adherence([], $from, $to))->toBe(0)
        ->and(Intervention::adherence(['2026-10-01'], $from, $from))->toBe(100);
});

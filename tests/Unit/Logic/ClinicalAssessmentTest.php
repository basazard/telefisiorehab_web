<?php

use App\Enums\PhysioDomain;
use App\Support\Onboarding\ClinicalAssessment;
use App\Support\Onboarding\Instruments;

function domainFor(?int $age = 35, array $conditions = [], array $complaints = ['pain'], ?string $occupation = 'Pekerja kantor'): PhysioDomain
{
    return ClinicalAssessment::pickDomain($age, $conditions, $complaints, $occupation)['domain'];
}

// ---------------------------------------------------------------- domain identification

it('picks one of the 7 domains from the main complaint and profile', function () {
    expect(domainFor())->toBe(PhysioDomain::Musculoskeletal)
        ->and(domainFor(age: 10))->toBe(PhysioDomain::Pediatric)
        ->and(domainFor(conditions: ['neurological']))->toBe(PhysioDomain::Neuromuscular)
        ->and(domainFor(complaints: ['balance']))->toBe(PhysioDomain::Neuromuscular)
        ->and(domainFor(complaints: ['breathless']))->toBe(PhysioDomain::Cardiopulmonary)
        ->and(domainFor(conditions: ['lung_disease']))->toBe(PhysioDomain::Cardiopulmonary)
        ->and(domainFor(complaints: ['post_injury']))->toBe(PhysioDomain::PostOpInjury)
        ->and(domainFor(conditions: ['post_surgery']))->toBe(PhysioDomain::PostOpInjury)
        ->and(domainFor(age: 70))->toBe(PhysioDomain::Geriatric)
        ->and(domainFor(occupation: 'Atlet'))->toBe(PhysioDomain::Sports);
});

it('applies the rules in priority order', function () {
    expect(domainFor(age: 10, conditions: ['neurological']))->toBe(PhysioDomain::Pediatric)
        ->and(domainFor(age: 70, complaints: ['breathless']))->toBe(PhysioDomain::Cardiopulmonary)
        ->and(domainFor(age: 70, conditions: ['post_surgery']))->toBe(PhysioDomain::PostOpInjury)
        ->and(domainFor(age: 65, occupation: 'Atlet'))->toBe(PhysioDomain::Geriatric);
});

it('explains why a domain was chosen', function () {
    expect(ClinicalAssessment::pickDomain(70, [], ['pain'], null)['reason'])->toBe('Usia 60 tahun ke atas');
});

// ---------------------------------------------------------------- instrument selection

it('picks the region-specific questionnaire, PSFS otherwise', function () {
    expect(ClinicalAssessment::regionInstruments(['Left Knee']))->toBe(['lefs'])
        ->and(ClinicalAssessment::regionInstruments(['Lower Back', 'Right Shoulder']))->toBe(['odi', 'spadi'])
        ->and(ClinicalAssessment::regionInstruments(['Neck']))->toBe(['psfs'])
        ->and(ClinicalAssessment::regionInstruments(['Upper Back']))->toBe(['psfs'])
        ->and(ClinicalAssessment::regionInstruments([]))->toBe(['psfs']);
});

it('renders instruments by domain', function () {
    expect(ClinicalAssessment::instruments(PhysioDomain::Geriatric, []))->toBe(['steadi', 'tug', 'barthel'])
        ->and(ClinicalAssessment::instruments(PhysioDomain::Cardiopulmonary, []))->toBe(['mmrc', 'sts', 'vitals'])
        ->and(ClinicalAssessment::instruments(PhysioDomain::PostOpInjury, ['Right Knee']))->toBe(['lefs', 'edema', 'wound'])
        ->and(ClinicalAssessment::instruments(PhysioDomain::Sports, ['Left Ankle']))->toBe(['lefs', 'psfs'])
        ->and(ClinicalAssessment::instruments(PhysioDomain::Sports, []))->toBe(['psfs'])
        ->and(ClinicalAssessment::instruments(PhysioDomain::Pediatric, ['Left Knee']))->toBe([]);
});

// ---------------------------------------------------------------- scoring

it('scores LEFS, ODI and SPADI', function () {
    expect(Instruments::score('lefs', array_fill(0, 20, 3))['value'])->toBe(60)
        ->and(Instruments::score('lefs', array_fill(0, 20, 3))['limited'])->toBeFalse()
        ->and(Instruments::score('lefs', [2, ...array_fill(0, 19, 3)])['limited'])->toBeTrue()
        ->and(Instruments::score('odi', array_fill(0, 10, 1))['value'])->toBe(20)
        ->and(Instruments::score('odi', array_fill(0, 10, 1))['limited'])->toBeFalse()
        ->and(Instruments::score('spadi', array_fill(0, 13, 10))['value'])->toBe(100);
});

it('leaves a skipped ODI section out of the percentage', function () {
    $answers = array_fill(0, 10, 2);
    $answers[7] = null;

    expect(Instruments::score('odi', $answers)['value'])->toBe(40);   // 18 / 45
});

it('scores PSFS on the filled activities only', function () {
    $score = Instruments::score('psfs', [
        ['activity' => 'Naik tangga', 'score' => 6],
        ['activity' => 'Jongkok', 'score' => 8],
        ['activity' => '', 'score' => null],
    ]);

    expect($score['value'])->toBe(7.0)->and($score['limited'])->toBeFalse();
});

it('scores Barthel and the fall-risk checklist', function () {
    $independent = ['feeding' => 10, 'bathing' => 5, 'grooming' => 5, 'dressing' => 10, 'bowels' => 10,
        'bladder' => 10, 'toilet' => 10, 'transfer' => 15, 'mobility' => 15, 'stairs' => 10];

    expect(Instruments::score('barthel', $independent)['value'])->toBe(100)
        ->and(Instruments::score('barthel', $independent)['limited'])->toBeFalse()
        ->and(Instruments::score('barthel', ['stairs' => 5] + $independent)['limited'])->toBeTrue();

    $steadi = array_fill(0, 12, 'no');
    $steadi[0] = 'yes';                                                       // fallen: 2 points
    expect(Instruments::score('steadi', $steadi)['limited'])->toBeFalse();
    $steadi[1] = 'yes';                                                       // walker: 2 points
    expect(Instruments::score('steadi', $steadi)['value'])->toBe(4)
        ->and(Instruments::score('steadi', $steadi)['limited'])->toBeTrue();
});

it('flags TUG at 12 seconds or when the patient cannot do it', function () {
    expect(Instruments::score('tug', ['unable' => false, 'seconds' => '11.9'])['limited'])->toBeFalse()
        ->and(Instruments::score('tug', ['unable' => false, 'seconds' => '12'])['limited'])->toBeTrue()
        ->and(Instruments::score('tug', ['unable' => true, 'seconds' => null])['limited'])->toBeTrue();
});

it('scores the cardiopulmonary instruments', function () {
    expect(Instruments::score('mmrc', ['grade' => 1])['limited'])->toBeFalse()
        ->and(Instruments::score('mmrc', ['grade' => 2])['limited'])->toBeTrue()
        ->and(Instruments::score('sts', ['unable' => false, 'reps' => 25, 'borg' => 3])['limited'])->toBeFalse()
        ->and(Instruments::score('sts', ['unable' => false, 'reps' => 19, 'borg' => 3])['limited'])->toBeTrue()
        ->and(Instruments::score('sts', ['unable' => false, 'reps' => 25, 'borg' => 5])['limited'])->toBeTrue()
        ->and(Instruments::score('vitals', ['spo2' => null, 'hr' => null, 'rr' => null])['limited'])->toBeFalse()
        ->and(Instruments::score('vitals', ['spo2' => 96, 'hr' => 80, 'rr' => 16])['limited'])->toBeFalse()
        ->and(Instruments::score('vitals', ['spo2' => 93, 'hr' => '', 'rr' => ''])['limited'])->toBeTrue()
        ->and(Instruments::score('vitals', ['spo2' => 93, 'hr' => null, 'rr' => null])['warning'])->toBeNull()
        ->and(Instruments::score('vitals', ['spo2' => 88, 'hr' => null, 'rr' => null])['warning'])->not->toBeNull();
});

it('scores edema and wound status', function () {
    expect(Instruments::score('edema', ['level' => 'mild', 'affected_cm' => null, 'healthy_cm' => null])['limited'])->toBeFalse()
        ->and(Instruments::score('edema', ['level' => 'moderate', 'affected_cm' => null, 'healthy_cm' => null])['limited'])->toBeTrue()
        ->and(Instruments::score('edema', ['level' => 'mild', 'affected_cm' => '38', 'healthy_cm' => '36'])['limited'])->toBeTrue()
        ->and(Instruments::score('wound', ['status' => 'clean'])['limited'])->toBeFalse()
        ->and(Instruments::score('wound', ['status' => 'infection'])['warning'])->not->toBeNull();
});

// ---------------------------------------------------------------- decision logic

it('branches to prevention education when nothing is limited', function () {
    $result = ClinicalAssessment::analyse(['lefs'], ['lefs' => array_fill(0, 20, 4)], 2);

    expect($result['has_limitation'])->toBeFalse()
        ->and($result['outcome'])->toBe(ClinicalAssessment::OUTCOME_EDUCATION)
        ->and($result['findings'])->toBe([]);
});

it('branches to risk classification on a limited instrument or pain >= 4', function () {
    expect(ClinicalAssessment::analyse(['lefs'], ['lefs' => array_fill(0, 20, 2)], 0)['outcome'])->toBe(ClinicalAssessment::OUTCOME_RISK)
        ->and(ClinicalAssessment::analyse(['lefs'], ['lefs' => array_fill(0, 20, 4)], 4)['outcome'])->toBe(ClinicalAssessment::OUTCOME_RISK);
});

it('errs on the safe side when no self-report instrument applies', function () {
    $result = ClinicalAssessment::analyse([], [], 0);

    expect($result['has_limitation'])->toBeTrue()->and($result['findings'])->not->toBeEmpty();
});

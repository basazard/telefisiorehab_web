<?php

use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\Instruments;
use App\Support\Onboarding\Programs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 10 W6: a licensed instrument can be switched off and every flow still completes.

beforeEach(function () {
    $this->withoutVite();
    $this->user = makeUser(Role::Patient, makeClinic());
    $this->patient = makePatient($this->user);
    $this->actingAs($this->user);
});

function switchOff(string ...$keys): void
{
    foreach ($keys as $key) {
        config(["clinical.instruments.$key.enabled" => false]);
    }
}

/** Fills PSFS with one activity scored $score on the assessment page. */
function fillPsfs($page, int $score)
{
    return $page->set('answers.psfs.0.activity', 'Membungkuk')->call('setAnswer', 'psfs.0.score', $score);
}

it('keeps every instrument on by default', function () {
    expect(Instruments::enabled('odi'))->toBeTrue()
        ->and(Instruments::enabled('spadi'))->toBeTrue()
        ->and(Instruments::enabled('borg'))->toBeTrue()
        ->and(Instruments::enabled('lefs'))->toBeTrue();
});

it('uses PSFS for the low back when ODI is off: assessment, risk and program complete', function () {
    switchOff('odi');
    completedScreening($this->patient, regions: ['Left Knee', 'Lower Back'], maxPain: 4);

    $assessment = startAssessment();
    expect($assessment->instruments)->toBe(['lefs', 'psfs']);

    $page = Volt::test('pages.patient.assessment')->assertDontSee('ODI');
    foreach (range(0, 19) as $i) {
        $page->call('setAnswer', "lefs.$i", 3);
    }
    $page->call('next')->assertSet('step', 2)->assertSee('PSFS');
    fillPsfs($page, 5)->call('next')->assertHasNoErrors()->assertRedirect(route('patient.assessment.result'));

    $assessment->refresh();
    expect($assessment->status)->toBe('completed')->and($assessment->tier)->toBe(RiskTier::Moderate)
        ->and(array_keys($assessment->scores))->toBe(['lefs', 'psfs']);

    $this->get(route('patient.risk-classification'))->assertOk()->assertDontSee('ODI');
    expect(Programs::build($assessment)->items()->count())->toBeGreaterThan(0);
});

it('switches an unfinished assessment from ODI to PSFS', function () {
    completedScreening($this->patient, regions: ['Lower Back'], maxPain: 4);
    $assessment = startAssessment();
    expect($assessment->instruments)->toBe(['odi']);

    switchOff('odi');
    Volt::test('pages.patient.assessment')->assertSee('PSFS')->assertDontSee('ODI');

    expect($assessment->fresh()->instruments)->toBe(['psfs']);
});

it('uses PSFS for the shoulder when SPADI is off', function () {
    switchOff('spadi');
    completedScreening($this->patient, regions: ['Right Shoulder'], maxPain: 3);

    expect(startAssessment()->instruments)->toBe(['psfs']);

    fillPsfs(Volt::test('pages.patient.assessment'), 8)->call('next')->assertHasNoErrors()->assertRedirect(route('patient.assessment.result'));
});

it('judges the sit-to-stand on repetitions only and never shows "Borg" when Borg CR10 is off', function () {
    switchOff('borg');
    $this->patient->update(['special_conditions' => ['lung_disease'], 'has_special_condition' => true]);
    completedScreening($this->patient, complaints: ['breathless'], regions: [], maxPain: 0);

    $assessment = startAssessment();
    expect($assessment->instruments)->toBe(['mmrc', 'sts', 'vitals']);

    $page = Volt::test('pages.patient.assessment')->call('setAnswer', 'mmrc.grade', 1)->call('next')->assertSet('step', 2);
    $page->assertSee('1-minute sit to stand')->assertDontSee('Borg')
        ->set('answers.sts.reps', '18')->call('next')->assertHasNoErrors()->assertSet('step', 3);   // no effort question to answer
    $page->call('next')->assertHasNoErrors()->assertRedirect(route('patient.assessment.result'));

    $assessment->refresh();
    expect($assessment->scores['sts']['display'])->toBe('18 kali')
        ->and($assessment->scores['sts']['limited'])->toBeTrue()                // 18 < 20 repetitions
        ->and($assessment->tier)->toBe(RiskTier::Moderate);

    $this->get(route('patient.assessment.result'))->assertOk()->assertDontSee('Borg');
    $this->get(route('patient.risk-classification'))->assertOk()->assertDontSee('Borg');
    expect(Programs::build($assessment)->items()->count())->toBeGreaterThan(0);
});

it('ignores the effort answer for the risk band when Borg CR10 is off', function () {
    $answers = ['sts' => ['unable' => false, 'reps' => 25, 'borg' => 9]];

    expect(Instruments::score('sts', $answers['sts'])['limited'])->toBeTrue();      // on: Borg 9 counts
    switchOff('borg');
    expect(Instruments::score('sts', $answers['sts'])['limited'])->toBeFalse()
        ->and(App\Support\Onboarding\RiskStratification::stratify(['sts'], $answers, 0)['tier'])->toBe(RiskTier::Low);
});

it('completes a monitoring without the switched-off instrument of the baseline', function () {
    $program = Programs::build(stratified(RiskTier::Moderate, regions: ['Lower Back'], pain: 6, instruments: ['odi'],
        answers: ['odi' => array_fill(0, 10, 2)]));
    $program->update(['next_evaluation_on' => today()]);
    switchOff('odi');

    Volt::test('pages.patient.evaluation')
        ->call('choose', 'pain', 3)->call('choose', 'globalChange', 'better')
        ->call('choose', 'newComplaint', 'no')->call('choose', 'sideEffect', 'no')
        ->assertDontSee('ODI')
        ->call('next')->assertHasNoErrors();

    $evaluation = ProgramEvaluation::sole();
    expect($evaluation->comparison['odi']['after'])->toBe('dinonaktifkan (lisensi)')
        ->and($evaluation->comparison['odi']['significant'])->toBeFalse()
        ->and($evaluation->comparison['nrs']['significant'])->toBeTrue()
        ->and($evaluation->outcome)->not->toBeNull();
});

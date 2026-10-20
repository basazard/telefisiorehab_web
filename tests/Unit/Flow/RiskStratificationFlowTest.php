<?php

use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\AssessmentResult;
use App\Support\Onboarding\Instruments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->clinic = makeClinic();
    $this->user = makeUser(Role::Patient, $this->clinic, ['name' => 'Pasien Luka']);
    $this->patient = makePatient($this->user);
    $this->actingAs($this->user);
});

/** Runs a one-instrument assessment to the end with the given answers. */
function finishWith(string $key, array $answers, int $maxPain = 2): AssessmentResult
{
    completedScreening(test()->patient, maxPain: $maxPain);
    $assessment = startAssessment();
    $assessment->update(['instruments' => [$key], 'answers' => [$key => array_replace(Instruments::blank($key), $answers)]]);

    $t = Volt::test('pages.patient.assessment');
    $t->call('next')->assertHasNoErrors();
    test()->lastRedirect = $t->effects['redirect'] ?? null;

    return $assessment->refresh();
}

// ---------------------------------------------------------------- scoring & storage

it('stratifies automatically when the assessment is finished', function () {
    $assessment = finishWith('mmrc', ['grade' => 2]);

    expect($assessment->tier)->toBe(RiskTier::Moderate)
        ->and($assessment->stratified_at)->not->toBeNull()
        ->and($assessment->risk_details['mmrc']['tier'])->toBe('moderate')
        ->and($assessment->risk_details['nrs']['tier'])->toBe('low')
        ->and($this->lastRedirect)->toBe(route('patient.assessment.result'));
});

it('stores Risiko Rendah on the prevention-education path', function () {
    $assessment = finishWith('mmrc', ['grade' => 0]);

    expect($assessment->has_limitation)->toBeFalse()->and($assessment->tier)->toBe(RiskTier::Low);
    $this->get(route('patient.assessment.result'))->assertSee('Risiko Rendah');
});

it('shows the category, the scores and the intervention CTA', function () {
    finishWith('mmrc', ['grade' => 2]);

    $this->get(route('patient.risk-classification'))
        ->assertOk()
        ->assertSee('Risiko Sedang')
        ->assertSee('Modified MRC Dyspnea Scale')
        ->assertSee('Lanjut ke Modul Rekomendasi Intervensi')
        ->assertSee('bukan</strong> diagnosis medis', false)
        ->assertDontSee('Perlu perhatian tenaga kesehatan');
});

it('warns on Risiko Tinggi but lets the patient continue under supervision', function () {
    $assessment = finishWith('tug', ['unable' => true]);

    expect($assessment->tier)->toBe(RiskTier::High)
        ->and($assessment->needsSupervision())->toBeTrue()
        ->and($this->patient->isRedFlagLocked())->toBeFalse();

    $this->get(route('patient.risk-classification'))
        ->assertOk()
        ->assertSee('Perlu perhatian tenaga kesehatan')
        ->assertSee('supervisi fisioterapis');
    $this->get(route('patient.intervention'))->assertOk();
});

it('stratifies an assessment finished before this module on first view', function () {
    $screening = completedScreening($this->patient, maxPain: 5);
    AssessmentResult::forceCreate([
        'clinic_id' => $this->clinic->id, 'screening_id' => $screening->id, 'patient_id' => $this->patient->id,
        'answers' => [], 'pain' => 5, 'domain' => 'pediatric', 'instruments' => [],
        'status' => 'completed', 'completed_at' => now(),
    ]);

    $this->get(route('patient.risk-classification'))->assertOk()->assertSee('Risiko Sedang');
    expect(AssessmentResult::first()->isStratified())->toBeTrue();
});

// ---------------------------------------------------------------- emergency escalation

it('stops the program like a red flag on an emergency finding', function () {
    $assessment = finishWith('wound', ['status' => 'infection']);

    expect($assessment->tier)->toBe(RiskTier::High)
        ->and($this->patient->isRedFlagLocked())->toBeTrue()
        ->and($assessment->screening->redFlagCheck->flagged)->toBe(['wound_infection'])
        ->and($this->lastRedirect)->toBe(route('patient.red-flag.stop'));

    $this->get(route('patient.risk-classification'))->assertRedirect(route('patient.red-flag.stop'));
    Volt::test('pages.patient.red-flag-stop')->assertSee('Tanda infeksi pada luka');
});

it('escalates a critical SpO2 and shows it to clinic staff', function () {
    finishWith('vitals', ['spo2' => 85]);
    expect($this->patient->isRedFlagLocked())->toBeTrue();

    $this->actingAs(makeUser(Role::Therapist, $this->clinic));
    Volt::test('pages.therapist.red-flag-review')
        ->assertSee('Pasien Luka')
        ->assertSee('Saturasi oksigen (SpO2) di bawah 90%');
});

it('lets the patient see the result again after staff unlock', function () {
    finishWith('wound', ['status' => 'infection']);
    $check = $this->patient->redFlagChecks()->first();

    $this->actingAs(makeUser(Role::Therapist, $this->clinic));
    Volt::test('pages.therapist.red-flag-review')
        ->set("notes.{$check->id}", 'Luka sudah diperiksa dokter, aman.')
        ->call('unlock', $check->id);

    $this->actingAs($this->user);
    expect($this->patient->isRedFlagLocked())->toBeFalse();
    $this->get(route('patient.risk-classification'))->assertOk()->assertSee('Risiko Tinggi');
    expect($this->patient->isRedFlagLocked())->toBeFalse();                 // viewing does not lock again
});

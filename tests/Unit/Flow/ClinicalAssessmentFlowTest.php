<?php

use App\Enums\PhysioDomain;
use App\Enums\Role;
use App\Models\AssessmentResult;
use App\Support\Onboarding\ClinicalAssessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->user = makeUser(Role::Patient);
    $this->patient = makePatient($this->user);          // female, born 1990, no conditions
    $this->actingAs($this->user);
});

// ---------------------------------------------------------------- domain identification

it('sends the patient back to the screening when none is completed', function () {
    $this->get(route('patient.domain'))->assertRedirect(route('patient.screening'));
});

it('shows the identified domain with its instruments', function () {
    completedScreening($this->patient);

    $this->get(route('patient.domain'))
        ->assertOk()
        ->assertSee('Muskuloskeletal')
        ->assertSee('LEFS')
        ->assertSee('Manual Muscle Testing')
        ->assertSee('tidak menggantikan pemeriksaan langsung');
});

it('stores the domain decided on the server as a snapshot', function () {
    $this->patient->update(['date_of_birth' => now()->subYears(70)->toDateString()]);
    $screening = completedScreening($this->patient);

    $assessment = startAssessment();

    expect($assessment->domain)->toBe(PhysioDomain::Geriatric)
        ->and($assessment->screening_id)->toBe($screening->id)
        ->and($assessment->instruments)->toBe(['steadi', 'tug', 'barthel'])
        ->and($assessment->status)->toBe('in_progress')
        ->and($assessment->clinic_id)->toBe($this->patient->clinic_id);
});

it('does not create a second assessment for the same screening', function () {
    completedScreening($this->patient);

    startAssessment();
    Volt::test('pages.patient.domain')->call('start');

    expect(AssessmentResult::count())->toBe(1);
});

// ---------------------------------------------------------------- the instruments

it('validates each instrument before moving on', function () {
    completedScreening($this->patient);
    startAssessment();

    Volt::test('pages.patient.assessment')
        ->call('next')
        ->assertHasErrors(['answers.lefs.0'])
        ->assertSet('step', 1);
});

it('only accepts answers for the instrument on screen', function () {
    completedScreening($this->patient, regions: ['Left Knee', 'Lower Back']);   // lefs, then odi
    startAssessment();

    Volt::test('pages.patient.assessment')
        ->call('setAnswer', 'odi.0', 5)
        ->assertStatus(422);
});

it('does not let the browser change the step', function () {
    completedScreening($this->patient);
    startAssessment();

    expect(fn () => Volt::test('pages.patient.assessment')->set('step', 2))->toThrow(Exception::class);
});

it('branches to prevention education when no limitation is found', function () {
    completedScreening($this->patient, maxPain: 2);
    $assessment = startAssessment();

    $t = Volt::test('pages.patient.assessment');
    foreach (range(0, 19) as $i) {
        $t->call('setAnswer', "lefs.$i", 4);
    }
    $t->call('next')->assertHasNoErrors()->assertRedirect(route('patient.assessment.result'));

    $assessment->refresh();
    expect($assessment->status)->toBe('completed')
        ->and($assessment->has_limitation)->toBeFalse()
        ->and($assessment->outcome)->toBe(ClinicalAssessment::OUTCOME_EDUCATION)
        ->and($assessment->lefs)->toBe(80)
        ->and($assessment->completed_at)->not->toBeNull();

    $this->get(route('patient.assessment.result'))
        ->assertOk()
        ->assertSee('Selesai')
        ->assertSee('edukasi pencegahan')
        ->assertSee(route('patient.intervention'));
});

it('branches to risk classification when a limitation is found', function () {
    completedScreening($this->patient, regions: ['Left Knee', 'Lower Back'], maxPain: 3);
    $assessment = startAssessment();

    $t = Volt::test('pages.patient.assessment');
    foreach (range(0, 19) as $i) {
        $t->call('setAnswer', "lefs.$i", 4);
    }
    $t->call('next')->assertSet('step', 2);
    foreach (range(0, 9) as $i) {
        $t->call('setAnswer', "odi.$i", 3);                                  // 60 %
    }
    $t->call('next')->assertRedirect(route('patient.assessment.result'));

    $assessment->refresh();
    expect($assessment->has_limitation)->toBeTrue()
        ->and($assessment->outcome)->toBe(ClinicalAssessment::OUTCOME_RISK)
        ->and($assessment->odi)->toBe(30)
        ->and($assessment->odi_percent)->toBe(60)
        ->and($assessment->findings)->toContain('ODI 60%: disabilitas punggung bawah');

    $this->get(route('patient.assessment.result'))
        ->assertOk()
        ->assertSee('Lanjut ke Klasifikasi Risiko');
    $this->get(route('patient.risk-classification'))->assertOk()->assertSee('Klasifikasi Risiko');
});

it('resumes an unfinished assessment at the saved step', function () {
    completedScreening($this->patient, regions: ['Left Knee', 'Lower Back']);
    startAssessment();

    $t = Volt::test('pages.patient.assessment');
    foreach (range(0, 19) as $i) {
        $t->call('setAnswer', "lefs.$i", 3);
    }
    $t->call('next');

    Volt::test('pages.patient.assessment')->assertSet('step', 2)->assertSet('answers.lefs.0', 3);
});

it('finishes a pediatric assessment without self-report and errs on the safe side', function () {
    $this->patient->update(['date_of_birth' => now()->subYears(8)->toDateString()]);
    completedScreening($this->patient);
    $assessment = startAssessment();

    Volt::test('pages.patient.assessment')->assertSee('Penilaian oleh fisioterapis')->call('next');

    expect($assessment->refresh()->domain)->toBe(PhysioDomain::Pediatric)
        ->and($assessment->has_limitation)->toBeTrue();
});

it('renders, validates and scores every instrument', function () {
    completedScreening($this->patient);
    $assessment = startAssessment();
    $all = ['lefs', 'odi', 'spadi', 'psfs', 'barthel', 'steadi', 'tug', 'mmrc', 'sts', 'vitals', 'edema', 'wound'];
    $assessment->update([
        'instruments' => $all,
        'answers' => array_combine($all, array_map([\App\Support\Onboarding\Instruments::class, 'blank'], $all)),
    ]);

    $t = Volt::test('pages.patient.assessment');
    $fill = [
        'lefs' => fn () => array_map(fn ($i) => $t->call('setAnswer', "lefs.$i", 4), range(0, 19)),
        'odi' => fn () => array_map(fn ($i) => $t->call('setAnswer', "odi.$i", 0), [0, 1, 2, 3, 4, 5, 6, 8, 9]),   // section 7 skipped
        'spadi' => fn () => array_map(fn ($i) => $t->call('setAnswer', "spadi.$i", 0), range(0, 12)),
        'psfs' => fn () => $t->set('answers.psfs.0.activity', 'Naik tangga')->call('setAnswer', 'psfs.0.score', 9),
        'barthel' => fn () => array_map(fn ($item) => $t->call('setAnswer', "barthel.$item", max(array_keys(\App\Support\Onboarding\Instruments::BARTHEL[$item][1]))),
            array_keys(\App\Support\Onboarding\Instruments::BARTHEL)),
        'steadi' => fn () => array_map(fn ($i) => $t->call('setAnswer', "steadi.$i", 'no'), range(0, 11)),
        'tug' => fn () => $t->set('answers.tug.seconds', '9.5'),
        'mmrc' => fn () => $t->call('setAnswer', 'mmrc.grade', 0),
        'sts' => fn () => $t->set('answers.sts.reps', '30')->call('setAnswer', 'sts.borg', 2),
        'vitals' => fn () => null,                                                    // optional
        'edema' => fn () => $t->call('setAnswer', 'edema.level', 'none'),
        'wound' => fn () => $t->call('setAnswer', 'wound.status', 'none'),
    ];

    foreach ($all as $n => $key) {
        $t->assertSet('step', $n + 1)->assertSee(\App\Support\Onboarding\Instruments::TITLES[$key]);
        $fill[$key]();
        $t->call('next')->assertHasNoErrors();
    }

    $assessment->refresh();
    expect($assessment->status)->toBe('completed')
        ->and(array_keys($assessment->scores))->toBe($all)
        ->and($assessment->has_limitation)->toBeFalse()
        ->and($assessment->odi_percent)->toBe(0);
});

// ---------------------------------------------------------------- access

it('blocks the assessment pages while a red-flag stop is active', function () {
    completedScreening($this->patient);
    lockPatient($this->patient);

    foreach (['patient.domain', 'patient.assessment', 'patient.assessment.result', 'patient.risk-classification', 'patient.intervention'] as $name) {
        $this->get(route($name))->assertRedirect(route('patient.red-flag.stop'));
    }
});

it('never shows another patient\'s assessment', function () {
    $other = makePatient(makeUser(Role::Patient));
    $screening = completedScreening($other);
    AssessmentResult::forceCreate([
        'screening_id' => $screening->id, 'patient_id' => $other->id, 'answers' => [], 'pain' => 0,
        'domain' => 'musculoskeletal', 'instruments' => [], 'status' => 'completed', 'completed_at' => now(),
    ]);

    $this->get(route('patient.assessment.result'))->assertRedirect(route('patient.domain'));
});

<?php

use App\Enums\ProgramStatus;
use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\AssessmentResult;
use App\Models\ConsultationRequest;
use App\Models\ExerciseLog;
use App\Models\Program;
use App\Support\Onboarding\Programs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->clinic = makeClinic();
    $this->user = makeUser(Role::Patient, $this->clinic, ['name' => 'Pasien Latihan']);
    $this->patient = makePatient($this->user);
    $this->actingAs($this->user);
});

/** A completed, stratified assessment with the given category. */
function stratified(RiskTier $tier, string $domain = 'musculoskeletal', array $regions = ['Left Knee'], int $pain = 2): AssessmentResult
{
    $screening = completedScreening(test()->patient, regions: $regions, maxPain: $pain);

    return AssessmentResult::forceCreate([
        'clinic_id' => test()->patient->clinic_id, 'screening_id' => $screening->id, 'patient_id' => test()->patient->id,
        'answers' => [], 'pain' => $pain, 'domain' => $domain, 'instruments' => [], 'status' => 'completed',
        'completed_at' => now(), 'tier' => $tier->value, 'risk_details' => [], 'stratified_at' => now(),
    ]);
}

function evaluationAnswers(array $o = []): array
{
    return $o + ['pain' => 2, 'function' => 7, 'mobility' => 7, 'global_change' => 'better', 'new_complaint' => false];
}

// ---------------------------------------------------------------- accurate path routing

it('gives Risiko Rendah a self-management program', function () {
    stratified(RiskTier::Low);

    Volt::test('pages.patient.intervention')
        ->assertSee('Self Management')
        ->assertSee('Latihan aerobik ringan')
        ->assertSee('Ergonomi')
        ->call('startProgram')
        ->assertRedirect(route('patient.program'));

    $program = Program::first();
    expect($program->status)->toBe(ProgramStatus::Active)
        ->and($program->pathway)->toBe('self_management')
        ->and($program->needs_monitoring)->toBeFalse()
        ->and($program->next_evaluation_on->toDateString())->toBe(today()->addWeeks(4)->toDateString())
        ->and($program->items()->with('exercise')->get()->pluck('exercise.slug')->all())
        ->toContain('hamstring-stretch', 'straight-leg-raise', 'easy-walk', 'progressive-relaxation');
});

it('gives Risiko Sedang a monitored, structured program and a telekonsultasi option', function () {
    stratified(RiskTier::Moderate, 'geriatric', ['Lower Back']);

    Volt::test('pages.patient.intervention')
        ->assertSee('Terbimbing')
        ->assertSee('Telekonsultasi fisioterapis')
        ->call('startProgram');

    $program = Program::first();
    $types = $program->items()->with('exercise')->get()->map(fn ($i) => $i->exercise->type->value)->unique()->values()->all();

    expect($program->pathway)->toBe('guided')
        ->and($program->needs_monitoring)->toBeTrue()
        ->and($program->next_evaluation_on->toDateString())->toBe(today()->addWeeks(2)->toDateString())
        ->and($types)->toBe(['breathing', 'rom', 'strengthening', 'balance'])
        ->and($program->items->first()->sets)->toBe(2);
});

it('never prescribes self-exercise for Risiko Tinggi', function () {
    $assessment = stratified(RiskTier::High);

    Volt::test('pages.patient.intervention')
        ->assertSee('Konsultasi / Rujukan')
        ->assertSee('Tidak diberikan latihan mandiri')
        ->assertDontSee('Mulai program latihan')
        ->assertSee('Rujukan ke fasilitas kesehatan')
        ->assertSee('Edukasi kondisi aman')
        ->call('startProgram')
        ->assertForbidden();

    expect(fn () => Programs::build($assessment))->toThrow(DomainException::class)
        ->and(Program::count())->toBe(0);

    $this->get(route('patient.program'))->assertOk()->assertSee('tidak diberikan latihan mandiri', false);
});

it('leaves pediatric programs to the physiotherapist', function () {
    stratified(RiskTier::Moderate, 'pediatric');

    Volt::test('pages.patient.intervention')
        ->assertSee('disusun langsung oleh fisioterapis')
        ->call('startProgram')
        ->assertForbidden();
});

it('records telekonsultasi and referral requests once while open', function () {
    stratified(RiskTier::High);

    Volt::test('pages.patient.intervention')
        ->set('reason', 'Bisa dihubungi sore hari')
        ->call('requestConsultation', 'referral')
        ->call('requestConsultation', 'referral')
        ->call('requestConsultation', 'teleconsultation')
        ->assertSee('Diproses');

    expect(ConsultationRequest::where('type', 'referral')->count())->toBe(1)
        ->and(ConsultationRequest::count())->toBe(2)
        ->and(ConsultationRequest::first()->reason)->toBe('Bisa dihubungi sore hari')
        ->and(ConsultationRequest::first()->clinic_id)->toBe($this->clinic->id);

    Volt::test('pages.patient.intervention')->call('requestConsultation', 'something-else')->assertHasErrors('type');
});

// ---------------------------------------------------------------- exercise log

it('logs an exercise once per day with the pain after it', function () {
    Programs::build(stratified(RiskTier::Low));
    $item = Program::first()->items->first();

    Volt::test('pages.patient.program')
        ->set("pain.{$item->id}", 3)
        ->call('complete', $item->id)
        ->call('complete', $item->id)
        ->assertSee('Selesai hari ini');

    $log = ExerciseLog::sole();
    expect($log->pain_score)->toBe(3)->and($log->sets_done)->toBe($item->sets)->and($log->patient_id)->toBe($this->patient->id);
});

it('does not log exercises of a paused program', function () {
    $program = Programs::build(stratified(RiskTier::Low));
    $program->update(['status' => ProgramStatus::NeedsReview]);

    Volt::test('pages.patient.program')
        ->assertSee('belum ada perbaikan')
        ->call('complete', $program->items->first()->id)
        ->assertForbidden();
});

// ---------------------------------------------------------------- monitoring & evaluation

it('reminds the patient when an evaluation is due', function () {
    $program = Programs::build(stratified(RiskTier::Low));
    $this->get(route('patient.dashboard'))->assertOk()->assertDontSee('saatnya evaluasi');

    $program->update(['next_evaluation_on' => today()]);
    $this->get(route('patient.dashboard'))->assertSee('saatnya evaluasi program Anda');
});

it('continues and raises the intensity when there is improvement', function () {
    $program = Programs::build(stratified(RiskTier::Low, pain: 5));
    $item = $program->items->first(fn ($i) => $i->hold_seconds === null && $i->minutes === null);   // a repetition exercise
    $reps = $item->reps;

    Volt::test('pages.patient.evaluation')
        ->call('choose', 'pain', 2)->call('choose', 'function', 7)->call('choose', 'mobility', 8)
        ->call('choose', 'globalChange', 'better')->call('choose', 'newComplaint', 'no')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Ya, ada perbaikan');

    $program->refresh();
    expect($program->status)->toBe(ProgramStatus::Active)
        ->and($program->intensity_level)->toBe(2)
        ->and($item->refresh()->reps)->toBe($reps + 2)
        ->and($program->evaluations()->sole()->improved)->toBeTrue();
});

it('pauses the program and offers reassessment when there is no improvement', function () {
    $program = Programs::build(stratified(RiskTier::Low, pain: 3));

    Programs::evaluate($program, evaluationAnswers(['global_change' => 'worse']));

    expect($program->refresh()->status)->toBe(ProgramStatus::NeedsReview);
    Volt::test('pages.patient.program')
        ->assertSee('Mulai reassessment')
        ->call('requestReconsultation');
    expect(ConsultationRequest::sole()->type)->toBe('teleconsultation');
    $this->get(route('patient.evaluation'))->assertRedirect(route('patient.program'));
});

it('validates the evaluation questionnaire', function () {
    Programs::build(stratified(RiskTier::Low));

    Volt::test('pages.patient.evaluation')->call('submit')->assertHasErrors(['pain', 'function', 'mobility', 'globalChange', 'newComplaint']);
});

it('stores adherence from the exercise log', function () {
    $program = Programs::build(stratified(RiskTier::Low));
    $program->update(['starts_on' => today()->subDays(3)]);
    $item = $program->items->first();
    foreach ([0, 2] as $daysAgo) {
        ExerciseLog::forceCreate(['patient_id' => $this->patient->id, 'program_exercise_id' => $item->id, 'performed_at' => now()->subDays($daysAgo)]);
    }

    expect(Programs::evaluate($program->refresh(), evaluationAnswers())->adherence)->toBe(50);   // 2 of 4 days
});

it('replaces the program after a new assessment, and stops it on Risiko Tinggi', function () {
    $first = Programs::build(stratified(RiskTier::Low));
    $second = Programs::build(stratified(RiskTier::Moderate));

    expect($first->refresh()->status)->toBe(ProgramStatus::Ended)->and($second->status)->toBe(ProgramStatus::Active);

    $high = AssessmentResult::forceCreate([
        'clinic_id' => $this->clinic->id, 'screening_id' => completedScreening($this->patient)->id, 'patient_id' => $this->patient->id,
        'answers' => ['tug' => ['unable' => true, 'seconds' => null]], 'pain' => 2, 'domain' => 'geriatric', 'instruments' => ['tug'],
        'status' => 'completed', 'completed_at' => now(),
    ]);
    $high->stratify();

    expect($second->refresh()->status)->toBe(ProgramStatus::Ended)
        ->and($this->patient->currentProgram())->toBeNull();
});

// ---------------------------------------------------------------- staff & access

it('shows requests to staff of the same clinic only, and lets them handle it', function () {
    stratified(RiskTier::High);
    Volt::test('pages.patient.intervention')->call('requestConsultation', 'teleconsultation');
    $request = ConsultationRequest::sole();

    $this->actingAs(makeUser(Role::Therapist, makeClinic('Klinik Lain')));
    Volt::test('pages.therapist.consultation-requests')->assertDontSee('Pasien Latihan');
    expect(fn () => Volt::test('pages.therapist.consultation-requests')->call('handle', $request->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);              // hidden by the clinic scope
    expect($request->refresh()->status)->toBe('open');

    $this->actingAs(makeUser(Role::Therapist, $this->clinic));
    Volt::test('pages.therapist.consultation-requests')
        ->assertSee('Pasien Latihan')
        ->assertSee('Risiko Tinggi')
        ->call('handle', $request->id)
        ->assertHasErrors(["notes.{$request->id}"])
        ->set("notes.{$request->id}", 'Telekonsultasi dijadwalkan Kamis pagi.')
        ->call('handle', $request->id)
        ->assertHasNoErrors();

    expect($request->refresh()->status)->toBe('handled')->and($request->staff_note)->toContain('Kamis');
});

it('blocks the new patient pages while a red-flag stop is active', function () {
    Programs::build(stratified(RiskTier::Low));
    lockPatient($this->patient);

    foreach (['patient.program', 'patient.evaluation', 'patient.history', 'patient.dashboard'] as $name) {
        $this->get(route($name))->assertRedirect(route('patient.red-flag.stop'));
    }
});

it('renders the history of logs, evaluations and assessments', function () {
    $program = Programs::build(stratified(RiskTier::Low));
    Programs::evaluate($program, evaluationAnswers());

    $this->get(route('patient.history'))->assertOk()->assertSee('Ada perbaikan')->assertSee('Risiko Rendah');
});

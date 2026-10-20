<?php

use App\Enums\ProgramStatus;
use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\ConsultationRequest;
use App\Models\ExerciseLog;
use App\Models\Program;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\Monitoring;
use App\Support\Onboarding\Programs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->clinic = makeClinic();
    $this->user = makeUser(Role::Patient, $this->clinic);
    $this->patient = makePatient($this->user);
    $this->actingAs($this->user);
});

function lefsAt(int $total): array
{
    $items = array_fill(0, 20, 0);
    for ($i = 0; $total > 0; $i++, $total -= 4) {
        $items[$i] = min(4, $total);
    }

    return $items;
}

/** A Risiko Sedang knee program whose baseline is LEFS 40 and pain 5. */
function kneeProgram(int $level = 1): Program
{
    $program = Programs::build(stratified(RiskTier::Moderate, pain: 5, instruments: ['lefs'], answers: ['lefs' => lefsAt(40)]));
    $program->update(['intensity_level' => $level]);
    Programs::redose($program);

    return $program->refresh();
}

/** Fills the monitoring wizard: general step, then LEFS. */
function monitorThroughUi(int $pain, int $lefs, string $global = 'better', string $new = 'no')
{
    $t = Volt::test('pages.patient.evaluation')
        ->call('choose', 'pain', $pain)->call('choose', 'globalChange', $global)
        ->call('choose', 'newComplaint', $new)->call('choose', 'sideEffect', 'no')
        ->call('toggleBarrier', 'time')
        ->call('next')->assertHasNoErrors()->assertSet('step', 2)
        ->assertSee('LEFS');

    foreach (lefsAt($lefs) as $i => $v) {
        $t->call('setAnswer', "lefs.$i", $v);
    }

    return $t->call('next')->assertHasNoErrors();
}

// ---------------------------------------------------------------- reminders & form

it('reminds the patient when monitoring is due', function () {
    $program = kneeProgram();
    $this->get(route('patient.dashboard'))->assertOk()->assertDontSee('saatnya monitoring');

    $program->update(['next_evaluation_on' => today()]);
    $this->get(route('patient.dashboard'))->assertSee('saatnya monitoring perkembangan Anda');
    $this->get(route('patient.program'))->assertSee('Isi monitoring');
});

it('validates the general questions before the instruments', function () {
    kneeProgram();

    Volt::test('pages.patient.evaluation')
        ->call('next')
        ->assertHasErrors(['pain', 'globalChange', 'newComplaint', 'sideEffect'])
        ->assertSet('step', 1)
        ->call('choose', 'sideEffect', 'yes')->call('next')
        ->assertHasErrors(['sideEffectText']);
});

it('re-runs the domain instruments and only accepts answers for the one on screen', function () {
    kneeProgram();

    Volt::test('pages.patient.evaluation')
        ->call('choose', 'pain', 3)->call('choose', 'globalChange', 'same')
        ->call('choose', 'newComplaint', 'no')->call('choose', 'sideEffect', 'no')
        ->call('next')
        ->assertSee('LEFS')
        ->call('next')->assertHasErrors(['answers.lefs.0'])
        ->call('setAnswer', 'odi.0', 1)->assertStatus(422);
});

// ---------------------------------------------------------------- adaptive branching

it('continues progressively on a significant improvement', function () {
    $program = kneeProgram();
    $count = $program->items()->count();

    monitorThroughUi(pain: 2, lefs: 50)
        ->assertSee('Perbandingan dengan data awal')
        ->assertSee('Membaik bermakna')
        ->assertSee('Lanjutkan program (progresif)');

    $e = ProgramEvaluation::sole();
    $program->refresh();
    expect($e->outcome)->toBe(Monitoring::OUTCOME_PROGRESS)
        ->and($e->improved)->toBeTrue()
        ->and($e->comparison['lefs'])->toMatchArray(['before' => '40/80', 'after' => '50/80', 'significant' => true])
        ->and($e->barriers)->toBe(['time'])
        ->and($program->intensity_level)->toBe(2)
        ->and($program->items()->count())->toBe($count + 1)                       // a more challenging variation
        ->and($program->next_evaluation_on->toDateString())->toBe(today()->addWeeks(2)->toDateString());
});

it('modifies the program: adherence first, then a lighter dose', function () {
    $program = kneeProgram(level: 2);
    $general = monitoringGeneral(['pain' => 4, 'global_change' => 'same']);

    Programs::monitor($program, $general, ['lefs' => lefsAt(44)]);
    expect($program->refresh()->intensity_level)->toBe(2)                         // no exercise logged: adherence 0 %
        ->and($program->status)->toBe(ProgramStatus::Active);

    $item = $program->items->first();
    foreach (range(0, 3) as $daysAgo) {
        ExerciseLog::forceCreate(['patient_id' => $this->patient->id, 'program_exercise_id' => $item->id, 'performed_at' => now()->subDays($daysAgo)]);
    }
    ProgramEvaluation::query()->update(['created_at' => now()->subDays(3)]);

    $e = Programs::monitor($program->refresh(), $general, ['lefs' => lefsAt(44)]);
    expect($e->outcome)->toBe(Monitoring::OUTCOME_MODIFY)
        ->and($e->adherence)->toBe(100)
        ->and($program->refresh()->intensity_level)->toBe(1);
});

it('pauses for reassessment after 4 weeks without improvement', function () {
    $program = kneeProgram();
    $program->update(['starts_on' => today()->subWeeks(5)]);

    $e = Programs::monitor($program->refresh(), monitoringGeneral(['pain' => 5, 'global_change' => 'same']), ['lefs' => lefsAt(42)]);

    expect($e->outcome)->toBe(Monitoring::OUTCOME_REASSESS)
        ->and($program->refresh()->status)->toBe(ProgramStatus::NeedsReview);

    Volt::test('pages.patient.program')->assertSee('Mulai reassessment')->call('requestReconsultation');
    expect(ConsultationRequest::sole()->type)->toBe('teleconsultation');
    $this->get(route('patient.evaluation'))->assertRedirect(route('patient.program'));
});

it('advises referral and pauses on drastic worsening', function () {
    $program = kneeProgram();

    monitorThroughUi(pain: 8, lefs: 40, global: 'worse')
        ->assertSee('Pertimbangkan rujukan medis')
        ->assertSee('Minta rujukan')
        ->call('requestConsultation', 'referral');

    expect($program->refresh()->status)->toBe(ProgramStatus::NeedsReview)
        ->and(ConsultationRequest::sole()->type)->toBe('referral');
});

it('escalates an emergency found during monitoring to a red-flag stop', function () {
    $program = Programs::build(stratified(RiskTier::Moderate, 'post_op_injury', instruments: ['wound'], answers: ['wound' => ['status' => 'clean']]));

    $e = Programs::monitor($program, monitoringGeneral(), ['wound' => ['status' => 'infection']]);

    expect($e->outcome)->toBe(Monitoring::OUTCOME_REFERRAL)
        ->and($this->patient->isRedFlagLocked())->toBeTrue()
        ->and($this->patient->redFlagChecks()->first()->flagged)->toContain('wound_infection');
});

// ---------------------------------------------------------------- goal achieved: maintenance program

it('moves the patient to a Maintenance Program once the goal is achieved', function () {
    $assessment = stratified(RiskTier::Moderate, pain: 5, instruments: ['lefs'], answers: ['lefs' => lefsAt(40)]);
    $program = Programs::build($assessment);

    monitorThroughUi(pain: 1, lefs: 72)
        ->assertSee('Tujuan tercapai: Maintenance Program')
        ->assertSee('Buka Maintenance Program');

    $maintenance = $this->patient->currentProgram();
    expect($program->refresh()->status)->toBe(ProgramStatus::Ended)
        ->and($program->end_reason)->toBe('Tujuan fisioterapi tercapai')
        ->and(ProgramEvaluation::sole()->goal_achieved)->toBeTrue()
        ->and($maintenance->isMaintenance())->toBeTrue()
        ->and($maintenance->needs_monitoring)->toBeFalse()
        ->and($maintenance->intensity_level)->toBe(2)
        ->and($maintenance->items()->first()->sets)->toBe(3)                       // maintenance doses as Risiko Rendah
        ->and($assessment->program()->first()->id)->toBe($program->id);           // the recommended program stays linked

    $this->get(route('patient.program'))->assertSee('Maintenance Program')->assertSee('tujuan fisioterapi tercapai');
});

it('keeps a maintenance program in the monitoring cycle', function () {
    $program = Programs::build(stratified(RiskTier::Low, pain: 2, instruments: ['lefs'], answers: ['lefs' => lefsAt(70)]));
    Programs::monitor($program, monitoringGeneral(['pain' => 1]), ['lefs' => lefsAt(76)]);
    $maintenance = $this->patient->currentProgram();

    $e = Programs::monitor($maintenance, monitoringGeneral(['pain' => 1]), ['lefs' => lefsAt(78)]);

    expect($e->goal_achieved)->toBeFalse()
        ->and($maintenance->refresh()->isActive())->toBeTrue()
        ->and($maintenance->next_evaluation_on->toDateString())->toBe(today()->addWeeks(4)->toDateString());
});

it('stores adherence since the last monitoring', function () {
    $program = kneeProgram();
    $program->update(['starts_on' => today()->subDays(3)]);
    $item = $program->items->first();
    foreach ([0, 2] as $daysAgo) {
        ExerciseLog::forceCreate(['patient_id' => $this->patient->id, 'program_exercise_id' => $item->id, 'performed_at' => now()->subDays($daysAgo)]);
    }

    expect(Programs::monitor($program->refresh(), monitoringGeneral(), ['lefs' => lefsAt(50)])->adherence)->toBe(50);   // 2 of 4 days
});

it('shows the monitoring outcome in the history', function () {
    Programs::monitor(kneeProgram(), monitoringGeneral(['pain' => 2]), ['lefs' => lefsAt(50)]);

    $this->get(route('patient.history'))->assertOk()->assertSee('Lanjutkan program (progresif)');
});

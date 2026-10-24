<?php

use App\Enums\ProgramStatus;
use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\Exercise;
use App\Models\ExerciseLog;
use App\Models\PatientAccessLog;
use App\Models\Program;
use App\Models\ProgramEvaluation;
use App\Support\Onboarding\Intervention;
use App\Support\Onboarding\Programs;
use App\Support\Staff\Attention;
use App\Support\Staff\Audit;
use App\Support\Staff\ProgramActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 09 S3: program review, adjustment and creation by a physiotherapist (rules and safety, server side).

beforeEach(function () {
    $this->clinic = makeClinic('Klinik A');
    $this->ani = makeUser(Role::Therapist, $this->clinic);
    $this->patient = staffPatient('Sri Wahyuni', $this->clinic, $this->ani);
    $this->assessment = stratified(RiskTier::Moderate);
    $this->program = Programs::build($this->assessment);
});

/** Logs every current item of the program on each of the given days before today. */
function logProgram(Program $program, array $daysAgo): void
{
    foreach ($program->items as $item) {
        foreach ($daysAgo as $d) {
            ExerciseLog::factory()->create(['program_exercise_id' => $item->id, 'performed_at' => today()->subDays($d)->setTime(8, 0)]);
        }
    }
}

function audited(string $action): int
{
    return PatientAccessLog::where('action', $action)->count();
}

// ---------------------------------------------------------------- approve

it('approves an active program with an optional note', function () {
    ProgramActions::approve($this->ani, $this->program, 'Program sesuai kondisi pasien.');

    expect($this->program->fresh())
        ->review_type->toBe(Program::REVIEW_APPROVED)
        ->reviewed_by->toBe($this->ani->id)
        ->review_note->toBe('Program sesuai kondisi pasien.')
        ->and(audited(Audit::PROGRAM_REVIEWED))->toBe(1);
});

it('does not approve a paused program', function () {
    $this->program->update(['status' => ProgramStatus::NeedsReview]);

    expect(fn () => ProgramActions::approve($this->ani, $this->program))->toThrow(ValidationException::class)
        ->and(audited(Audit::PROGRAM_REVIEWED))->toBe(0);
});

// ---------------------------------------------------------------- adjust

it('needs a note for every adjustment', function () {
    expect(fn () => ProgramActions::adjust($this->ani, $this->program, 'kurang', level: 2))->toThrow(ValidationException::class)
        ->and($this->program->fresh()->intensity_level)->toBe(1);
});

it('re-doses the program after a level change and marks it adjusted', function () {
    ProgramActions::adjust($this->ani, $this->program, 'Pasien siap level berikutnya.', level: 2);

    $program = $this->program->fresh()->load('items.exercise');
    $item = $program->items->first(fn ($i) => $i->exercise->dose_unit === 'reps');

    expect($program->intensity_level)->toBe(2)
        ->and($program->review_type)->toBe(Program::REVIEW_ADJUSTED)
        ->and($item->reps)->toBe(Intervention::dose(RiskTier::Moderate, 'reps', $item->exercise->type->value, 2)['reps'])
        ->and(audited(Audit::PROGRAM_ADJUSTED))->toBe(1);
});

it('keeps the physiotherapist dose after the level re-dose', function () {
    $item = $this->program->items()->with('exercise')->get()->first(fn ($i) => $i->exercise->dose_unit === 'reps');

    ProgramActions::adjust($this->ani, $this->program, 'Repetisi dikurangi karena nyeri.', level: 2,
        doses: [$item->id => ['sets' => 2, 'reps' => 6]]);

    expect($item->fresh()->only('sets', 'reps'))->toBe(['sets' => 2, 'reps' => 6]);
});

it('refuses a dose outside the limits and changes nothing', function () {
    $item = $this->program->items()->with('exercise')->get()->first(fn ($i) => $i->exercise->dose_unit === 'reps');

    expect(fn () => ProgramActions::adjust($this->ani, $this->program, 'Dosis dinaikkan banyak.', doses: [$item->id => ['sets' => 9, 'reps' => 50]]))
        ->toThrow(ValidationException::class)
        ->and($item->fresh()->sets)->toBe($item->sets)
        ->and($this->program->fresh()->review_type)->toBeNull();
});

it('refuses an adjustment without a change', function () {
    expect(fn () => ProgramActions::adjust($this->ani, $this->program, 'Tidak ada yang berubah.', level: 1))->toThrow(ValidationException::class);
});

it('removes an item softly: its logs stay, the patient no longer sees it, adherence does not change', function () {
    logProgram($this->program, [1, 2, 3]);
    $program = $this->program->fresh();
    $item = $program->items->first();
    $logDates = fn () => $program->logs()->pluck('performed_at')->all();
    $before = Attention::adherence($program, $logDates());
    $logs = ExerciseLog::count();

    ProgramActions::adjust($this->ani, $program, 'Latihan ini memicu nyeri lutut.', remove: [$item->id]);

    expect($item->fresh()->removed_at)->not->toBeNull()
        ->and(ExerciseLog::count())->toBe($logs)
        ->and($program->fresh()->items->pluck('id'))->not->toContain($item->id)
        ->and($program->fresh()->allItems->pluck('id'))->toContain($item->id)
        ->and(Attention::adherence($program, $logDates()))->toBe($before);

    $this->actingAs($this->patient->user)->get(route('patient.program'))->assertOk()->assertDontSee($item->exercise->name);
});

it('never removes the last exercise', function () {
    expect(fn () => ProgramActions::adjust($this->ani, $this->program, 'Semua latihan dihapus.', remove: $this->program->items->pluck('id')->all()))
        ->toThrow(ValidationException::class);
});

it('adds an exercise from the global library or the clinic own, not from another clinic', function () {
    $have = $this->program->items->pluck('exercise_id')->all();
    $global = Exercise::withoutGlobalScopes()->whereNull('clinic_id')->whereNotIn('id', $have)->first();
    $own = Exercise::factory()->create(['clinic_id' => $this->clinic->id, 'name' => 'Latihan khusus klinik']);
    $foreign = Exercise::factory()->create(['clinic_id' => makeClinic('Klinik B')->id]);

    ProgramActions::adjust($this->ani, $this->program, 'Tambah dua latihan baru.', add: [$global->id, $own->id]);

    expect($this->program->fresh()->items->pluck('exercise_id'))->toContain($global->id)->toContain($own->id);
    expect(fn () => ProgramActions::adjust($this->ani, $this->program, 'Tambah latihan klinik lain.', add: [$foreign->id]))
        ->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------- needs review, end, goal

it('resumes a paused program, one level lighter when asked', function () {
    $this->program->update(['status' => ProgramStatus::NeedsReview, 'intensity_level' => 2, 'next_evaluation_on' => null]);

    ProgramActions::resume($this->ani, $this->program, 'Lanjut dengan dosis lebih ringan.', lighter: true);

    expect($this->program->fresh())
        ->status->toBe(ProgramStatus::Active)
        ->intensity_level->toBe(1)
        ->next_evaluation_on->toDateString()->toBe(today()->addWeeks($this->program->evaluation_interval_weeks)->toDateString())
        ->and(audited(Audit::PROGRAM_RESUMED))->toBe(1);
});

it('ends a program with a reason, keeping its logs and evaluations', function () {
    logProgram($this->program, [1, 2]);
    ProgramEvaluation::factory()->create(['program_id' => $this->program->id]);
    $counts = [ExerciseLog::count(), ProgramEvaluation::count()];

    ProgramActions::end($this->ani, $this->program, 'Pasien dirujuk ke dokter ortopedi.');

    expect($this->program->fresh())
        ->status->toBe(ProgramStatus::Ended)
        ->ended_by->toBe($this->ani->id)
        ->end_reason->toBe('Pasien dirujuk ke dokter ortopedi.')
        ->and([ExerciseLog::count(), ProgramEvaluation::count()])->toBe($counts)
        ->and(audited(Audit::PROGRAM_ENDED))->toBe(1);

    $this->actingAs($this->patient->user)->get(route('patient.dashboard'))->assertOk()
        ->assertSee('Program diakhiri oleh fisioterapis')->assertSee('Pasien dirujuk ke dokter ortopedi.')->assertSee('Mulai reassessment');
});

it('marks the goal achieved: the program ends and a maintenance program follows', function () {
    $maintenance = ProgramActions::goalAchieved($this->ani, $this->program);

    expect($this->program->fresh())
        ->status->toBe(ProgramStatus::Ended)
        ->end_reason->toBe('Tujuan fisioterapi tercapai')
        ->and($maintenance->isMaintenance())->toBeTrue()
        ->and($this->patient->currentProgram()->id)->toBe($maintenance->id)
        ->and(audited(Audit::GOAL_ACHIEVED))->toBe(1);
});

// ---------------------------------------------------------------- create

it('creates a guided, monitored, reviewed program for a pediatric patient', function () {
    $child = staffPatient('Kevin Kecil', $this->clinic, $this->ani);
    $child->update(['date_of_birth' => today()->subYears(8)]);
    $this->patient = $child;
    stratified(RiskTier::Moderate, domain: 'pediatric');
    expect(fn () => Programs::build($child->assessments()->latest('id')->first()))->toThrow(DomainException::class);   // the system refuses

    $exercise = Exercise::withoutGlobalScopes()->whereNull('clinic_id')->where('dose_unit', 'reps')->first();
    $program = ProgramActions::create($this->ani, $child, 'Program anak', [$exercise->id => ['sets' => 2, 'reps' => 8]], 'Lakukan bersama orang tua.');

    expect($program)
        ->status->toBe(ProgramStatus::Active)
        ->pathway->toBe('guided')
        ->needs_monitoring->toBeTrue()
        ->created_by->toBe($this->ani->id)
        ->evaluation_interval_weeks->toBe(2)
        ->review_type->toBe(Program::REVIEW_ADJUSTED)
        ->and($program->next_evaluation_on->toDateString())->toBe(today()->addWeeks(2)->toDateString())
        ->and($program->items->first()->only('sets', 'reps'))->toBe(['sets' => 2, 'reps' => 8])
        ->and(audited(Audit::PROGRAM_CREATED))->toBe(1);
});

it('refuses to create a program for a high-risk, a locked, an unassessed patient or one with a program', function () {
    $exercise = Exercise::withoutGlobalScopes()->whereNull('clinic_id')->where('dose_unit', 'reps')->first();
    $items = [$exercise->id => ['sets' => 2, 'reps' => 8]];

    $high = staffPatient('Risiko Tinggi', $this->clinic, $this->ani);
    $this->patient = $high;
    stratified(RiskTier::High);

    $locked = staffPatient('Terkunci', $this->clinic, $this->ani);
    $this->patient = $locked;
    stratified(RiskTier::Moderate);
    lockPatient($locked);

    $unassessed = staffPatient('Belum Assessment', $this->clinic, $this->ani);
    $withProgram = $this->program->patient;

    foreach ([$high, $locked, $unassessed, $withProgram] as $patient) {
        expect(fn () => ProgramActions::create($this->ani, $patient, 'Program baru', $items))->toThrow(ValidationException::class);
    }

    expect(Program::count())->toBe(1)->and(audited(Audit::PROGRAM_CREATED))->toBe(0);
});

<?php

use App\Enums\ProgramStatus;
use App\Enums\Role;
use App\Models\ConsultationRequest;
use App\Models\PatientAccessLog;
use App\Models\ProgramEvaluation;
use App\Models\RedFlagCheck;
use App\Models\Screening;
use App\Support\Staff\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 09 S2: the staff patient detail.

beforeEach(function () {
    $this->withoutVite();

    $this->clinicA = makeClinic('Klinik A');
    $this->ani = makeUser(Role::Therapist, $this->clinicA, ['name' => 'Ani Terapis']);
    $this->budi = makeUser(Role::Therapist, $this->clinicA);
    $this->adminA = makeUser(Role::ClinicAdmin, $this->clinicA);

    $this->patient = staffPatient('Sri Wahyuni', $this->clinicA, $this->ani, 'moderate');
    $this->patient->user->update(['phone' => '0812-3456-7890']);
    $this->program = programWithLogs($this->patient, 'guided', 10, [1, 2, 3]);
});

function detailUrl($patient): string
{
    return route('therapist.patients.show', $patient->id);
}

// ---------------------------------------------------------------- access

it('opens for the assigned therapist and the clinic admin', function () {
    foreach ([$this->ani, $this->adminA] as $user) {
        $this->actingAs($user)->get(detailUrl($this->patient))->assertOk()
            ->assertSee('Sri Wahyuni')->assertSee('0812-3456-7890')->assertSee('Ani Terapis');
    }
});

it('refuses another therapist of the same clinic', function () {
    $this->actingAs($this->budi)->get(detailUrl($this->patient))->assertForbidden();
});

it('refuses staff of another clinic with a 403', function () {
    $clinicB = makeClinic('Klinik B');

    $this->actingAs(makeUser(Role::Therapist, $clinicB))->get(detailUrl($this->patient))->assertForbidden();
    $this->actingAs(makeUser(Role::ClinicAdmin, $clinicB))->get(detailUrl($this->patient))->assertForbidden();
});

it('refuses patients, also the patient themselves', function () {
    $this->actingAs($this->patient->user)->get(detailUrl($this->patient))->assertForbidden();
});

it('refuses an unassigned patient to a therapist until claimed', function () {
    $unassigned = staffPatient('Belum Diambil', $this->clinicA);

    $this->actingAs($this->ani)->get(detailUrl($unassigned))->assertForbidden();
});

it('links the patient list and the dashboard to the detail', function () {
    $this->actingAs($this->ani)->get(route('therapist.patients'))->assertSee(detailUrl($this->patient), false);
});

// ---------------------------------------------------------------- audit

it('writes one audit row when the detail is opened', function () {
    $this->actingAs($this->ani);

    Volt::test('pages.therapist.patient-detail', ['patient' => $this->patient->id])->set('note', 'abc');

    expect(PatientAccessLog::where(['patient_id' => $this->patient->id, 'user_id' => $this->ani->id, 'action' => Audit::VIEWED_DETAIL])->count())->toBe(1);
});

it('writes no audit row when access is refused', function () {
    $this->actingAs($this->budi)->get(detailUrl($this->patient));

    expect(PatientAccessLog::count())->toBe(0);
});

it('never changes or deletes an audit row', function () {
    $row = Audit::record($this->ani, $this->patient, Audit::VIEWED_DETAIL);

    expect(fn () => $row->update(['action' => 'x']))->toThrow(LogicException::class)
        ->and(fn () => $row->delete())->toThrow(LogicException::class)
        ->and(PatientAccessLog::count())->toBe(1);
});

// ---------------------------------------------------------------- figures

it('shows the summary of the latest assessment and the program', function () {
    $this->actingAs($this->ani)->get(detailUrl($this->patient))->assertOk()
        ->assertSee('Risiko Sedang')->assertSee('Lutut kiri')->assertSee('Nyeri')->assertSee('Mengurangi nyeri', false);
});

it('feeds the charts with the patient data', function () {
    $this->actingAs($this->ani);
    ProgramEvaluation::factory()->create(['program_id' => $this->program->id, 'pain' => 2, 'created_at' => today()->subDay()->setTime(19, 0)]);

    $page = Volt::test('pages.therapist.patient-detail', ['patient' => $this->patient->id]);
    $weeks = $page->viewData('weeks');
    $pain = $page->viewData('pain');

    // program started 10 days ago: week 1 = days 10..4 ago (no logs), week 2 = days 3..0 ago (3 of 4 days)
    expect(array_column($weeks, 'percent'))->toBe([0, 75])
        ->and($pain['dates'])->toHaveCount(3)
        ->and($pain['logs'])->toBe([3.0, 3.0, 3.0])
        ->and(end($pain['evaluations']))->toBe(2);
});

it('lists the logs of the last 30 days and the evaluation timeline', function () {
    ProgramEvaluation::factory()->worse()->create(['program_id' => $this->program->id]);

    $this->actingAs($this->ani)->get(detailUrl($this->patient))->assertOk()
        ->assertSee('Log latihan 30 hari terakhir')->assertSee('Lebih buruk')->assertSee('Riwayat evaluasi');
});

it('shows empty states for a patient without a program', function () {
    $new = staffPatient('Tanpa Program', $this->clinicA, $this->ani);

    $this->actingAs($this->ani)->get(detailUrl($new))->assertOk()
        ->assertSee('Pasien belum memiliki program latihan.')
        ->assertSee('Belum ada evaluasi.')
        ->assertSee('Belum ada latihan tercatat dalam 30 hari terakhir.')
        ->assertSee('Belum ada screening.');
});

it('shows the badges and the red-flag history with who unlocked it', function () {
    $this->program->update(['status' => ProgramStatus::NeedsReview]);
    ConsultationRequest::factory()->create(['patient_id' => $this->patient->id, 'reason' => 'Mohon telepon saya.']);
    $old = RedFlagCheck::factory()->stopped(['night_pain'])->create([
        'screening_id' => Screening::factory()->stopped()->create(['patient_id' => $this->patient->id]),
    ]);
    $old->update(['unlocked_at' => now(), 'unlocked_by' => $this->budi->id, 'unlock_note' => 'Sudah diperiksa dokter, aman.']);
    lockPatient($this->patient);

    $this->actingAs($this->ani)->get(detailUrl($this->patient))->assertOk()
        ->assertSee('Red flag: program dihentikan')->assertSee('Konsultasi terbuka')->assertSee('Program perlu ditinjau')
        ->assertSee('Mohon telepon saya.')
        ->assertSee('Sudah diperiksa dokter, aman.')->assertSee($this->budi->name)
        ->assertSee('Nyeri dada atau dada terasa tertekan');
});

it('loads the detail with a fixed number of queries (no N+1)', function () {
    $this->actingAs($this->ani);
    $count = function () {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Volt::test('pages.therapist.patient-detail', ['patient' => $this->patient->id]);
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $count();                                   // warm-up
    $before = $count();
    $more = programWithLogs($this->patient, 'self_management', 5, [0, 1, 2, 3, 4]);
    ProgramEvaluation::factory()->count(3)->create(['program_id' => $more->id]);
    Audit::record($this->ani, $this->patient, Audit::NOTE_ADDED);

    expect($count())->toBe($before);
});

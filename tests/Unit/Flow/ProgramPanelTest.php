<?php

use App\Enums\ProgramStatus;
use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\Exercise;
use App\Models\Program;
use App\Support\Onboarding\Programs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 09 S3: ProgramPolicy and the "Program" tab of the staff patient detail.

beforeEach(function () {
    $this->withoutVite();

    $this->clinic = makeClinic('Klinik A');
    $this->ani = makeUser(Role::Therapist, $this->clinic, ['name' => 'Ani Terapis']);
    $this->budi = makeUser(Role::Therapist, $this->clinic);
    $this->admin = makeUser(Role::ClinicAdmin, $this->clinic);
    $this->patient = staffPatient('Sri Wahyuni', $this->clinic, $this->ani);
    $this->program = Programs::build(stratified(RiskTier::Moderate));
});

function panel()
{
    return Volt::test('staff.program-panel', ['patientId' => test()->patient->id]);
}

// ---------------------------------------------------------------- policy

it('lets the assigned therapist, the clinic admin and the platform admin change a program', function () {
    foreach ([$this->ani, $this->admin, makeUser(Role::PlatformAdmin)] as $user) {
        expect($user->can('update', $this->program))->toBeTrue()
            ->and($user->can('end', $this->program))->toBeTrue()
            ->and($user->can('create', [Program::class, $this->patient]))->toBeTrue();
    }
});

it('refuses another therapist, another clinic and the patient', function () {
    $others = [$this->budi, makeUser(Role::Therapist, makeClinic('Klinik B')), makeUser(Role::ClinicAdmin, makeClinic('Klinik C')), $this->patient->user];

    foreach ($others as $user) {
        expect($user->can('update', $this->program))->toBeFalse()
            ->and($user->can('end', $this->program))->toBeFalse()
            ->and($user->can('create', [Program::class, $this->patient]))->toBeFalse();
    }
});

it('never lets anyone delete a program', function () {
    expect(makeUser(Role::PlatformAdmin)->can('delete', $this->program))->toBeFalse();
});

// ---------------------------------------------------------------- panel

it('shows the program tab on the patient detail', function () {
    $this->actingAs($this->ani)->get(route('therapist.patients.show', $this->patient->id))->assertOk()
        ->assertSee('Setujui program')->assertSee('Sesuaikan')->assertSee('Riwayat program');
});

it('refuses the panel to a therapist the patient is not assigned to', function () {
    $this->actingAs($this->budi);

    panel()->assertForbidden();
});

it('approves from the panel and the patient sees the badge and the note', function () {
    $this->actingAs($this->ani);
    panel()->set('approveNote', 'Lakukan pelan-pelan, jangan memaksa.')->call('approve')->assertHasNoErrors()->assertSee('Disetujui fisioterapis');

    $this->actingAs($this->patient->user)->get(route('patient.program'))->assertOk()
        ->assertSee('Disetujui fisioterapis')->assertSee('Lakukan pelan-pelan, jangan memaksa.');
});

it('adjusts from the panel and the patient sees "Disesuaikan fisioterapis"', function () {
    $this->actingAs($this->ani);

    panel()->call('startAdjust')->set('level', 2)->set('note', 'Naik ke level dua minggu ini.')->call('saveAdjust')->assertHasNoErrors();

    expect($this->program->fresh()->intensity_level)->toBe(2);
    $this->actingAs($this->patient->user)->get(route('patient.program'))->assertSee('Disesuaikan fisioterapis');
});

it('shows the note error from the panel', function () {
    $this->actingAs($this->ani);

    panel()->call('startAdjust')->set('level', 2)->set('note', 'pendek')->call('saveAdjust')->assertHasErrors('note');
});

it('refuses an action after the patient was reassigned', function () {
    $this->actingAs($this->ani);
    $page = panel();
    $this->patient->update(['therapist_id' => $this->budi->id]);

    $page->call('approve')->assertForbidden();
    expect($this->program->fresh()->review_type)->toBeNull();
});

it('resumes and ends from the panel', function () {
    $this->program->update(['status' => ProgramStatus::NeedsReview]);
    $this->actingAs($this->ani);

    panel()->set('resumeNote', 'Lanjutkan, nyeri sudah turun.')->call('resume')->assertHasNoErrors();
    expect($this->program->fresh()->status)->toBe(ProgramStatus::Active);

    panel()->set('endReason', 'Pasien pindah ke klinik lain.')->call('endProgram')->assertHasNoErrors();
    expect($this->program->fresh()->status)->toBe(ProgramStatus::Ended);
});

it('creates a program from the panel for a patient without one', function () {
    $this->program->end('Diganti');
    $exercise = Exercise::withoutGlobalScopes()->whereNull('clinic_id')->where('dose_unit', 'reps')->first();
    $this->actingAs($this->ani);

    panel()->assertSee('Buat program')
        ->call('startCreate')->call('pick', $exercise->id)
        ->set('title', 'Program terbimbing lutut')->call('saveCreate')->assertHasNoErrors();

    expect($this->patient->currentProgram())->title->toBe('Program terbimbing lutut')->created_by->toBe($this->ani->id);
});

it('explains why no program can be created for a high-risk patient', function () {
    $this->program->end('Diganti');
    stratified(RiskTier::High);
    $this->actingAs($this->ani);

    panel()->assertSee('Risiko Tinggi: program latihan tidak diberikan')->assertDontSee('Buat program');
});

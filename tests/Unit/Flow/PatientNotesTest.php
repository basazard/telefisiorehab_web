<?php

use App\Enums\Role;
use App\Models\PatientAccessLog;
use App\Models\PatientNote;
use App\Support\Staff\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 09 S2: staff notes on the patient detail. Append only, clinic scoped, never shown to the patient.

beforeEach(function () {
    $this->withoutVite();

    $this->clinicA = makeClinic('Klinik A');
    $this->ani = makeUser(Role::Therapist, $this->clinicA, ['name' => 'Ani Terapis']);
    $this->adminA = makeUser(Role::ClinicAdmin, $this->clinicA);
    $this->patient = staffPatient('Sri Wahyuni', $this->clinicA, $this->ani, 'low');
});

function addNote(string $body)
{
    return Volt::test('pages.therapist.patient-detail', ['patient' => test()->patient->id])->set('note', $body)->call('addNote');
}

it('saves a note with its author and clinic, and audits it', function () {
    $this->actingAs($this->ani);

    addNote('Pasien dihubungi, nyeri lutut berkurang.')->assertHasNoErrors()->assertSee('Pasien dihubungi, nyeri lutut berkurang.');

    $note = PatientNote::sole();
    expect($note->user_id)->toBe($this->ani->id)
        ->and($note->clinic_id)->toBe($this->clinicA->id)
        ->and(PatientAccessLog::where(['action' => Audit::NOTE_ADDED, 'user_id' => $this->ani->id])->count())->toBe(1);
});

it('needs at least 10 characters', function () {
    $this->actingAs($this->ani);

    addNote('pendek')->assertHasErrors(['note' => 'min']);
    addNote('')->assertHasErrors(['note' => 'required']);

    expect(PatientNote::count())->toBe(0);
});

it('shows the newest note first to every staff member who may open the detail', function () {
    $this->actingAs($this->ani);
    addNote('Catatan pertama dari Ani.');
    $this->travel(1)->minutes();
    $this->actingAs($this->adminA);
    addNote('Catatan kedua dari admin.');

    $this->actingAs($this->ani)->get(route('therapist.patients.show', $this->patient->id))
        ->assertSeeInOrder(['Catatan kedua dari admin.', 'Catatan pertama dari Ani.']);
});

it('never changes or deletes a note', function () {
    $this->actingAs($this->ani);
    addNote('Catatan yang tidak boleh diubah.');
    $note = PatientNote::sole();

    expect(fn () => $note->update(['body' => 'diubah']))->toThrow(LogicException::class)
        ->and(fn () => $note->delete())->toThrow(LogicException::class)
        ->and($note->fresh()->body)->toBe('Catatan yang tidak boleh diubah.');
});

it('keeps notes inside the clinic', function () {
    $this->actingAs($this->ani);
    addNote('Catatan rahasia klinik A.');

    $this->actingAs(makeUser(Role::ClinicAdmin, makeClinic('Klinik B')));
    expect(PatientNote::count())->toBe(0);                                   // clinic scope
});

it('never shows notes to the patient', function () {
    $this->actingAs($this->ani);
    addNote('Catatan rahasia untuk staf.');

    $this->actingAs($this->patient->user);

    expect($this->patient->user->can('addNote', $this->patient))->toBeFalse();
    $this->get(route('therapist.patients.show', $this->patient->id))->assertForbidden();

    foreach (['patient.dashboard', 'patient.program', 'patient.history'] as $route) {
        $this->get(route($route))->assertDontSee('Catatan rahasia untuk staf.');
    }
});

it('refuses a note from someone who may not open the detail', function () {
    $this->actingAs($this->ani);
    $page = Volt::test('pages.therapist.patient-detail', ['patient' => $this->patient->id]);

    $this->patient->update(['therapist_id' => makeUser(Role::Therapist, $this->clinicA)->id]);   // reassigned meanwhile

    $page->set('note', 'Catatan setelah dipindahkan.')->call('addNote')->assertForbidden();
    expect(PatientNote::count())->toBe(0);
});

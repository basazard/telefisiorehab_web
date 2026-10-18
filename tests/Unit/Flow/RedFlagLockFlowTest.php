<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->clinic = makeClinic('Klinik A');
    $this->otherClinic = makeClinic('Klinik B');

    $this->patientUser = makeUser(Role::Patient, $this->clinic, ['name' => 'Pasien Merah']);
    $this->patient = makePatient($this->patientUser);
});

// ---------------------------------------------------------------- the patient side

it('blocks every patient page while a stop is active', function () {
    lockPatient($this->patient);
    $this->actingAs($this->patientUser);

    foreach (['patient.dashboard', 'patient.screening', 'patient.domain'] as $name) {
        $this->get(route($name))->assertRedirect(route('patient.red-flag.stop'));
    }
});

it('keeps the stop page itself reachable while locked', function () {
    lockPatient($this->patient);
    $this->withoutVite();

    $this->actingAs($this->patientUser)
        ->get(route('patient.red-flag.stop'))
        ->assertOk()
        ->assertSee('Program latihan dihentikan');
});

it('tells the patient they cannot continue and must see a health facility', function () {
    lockPatient($this->patient, ['chest_pain', 'fainting']);
    $this->actingAs($this->patientUser);

    Volt::test('pages.patient.red-flag-stop')
        ->assertSee('belum dapat melanjutkan program latihan')
        ->assertSee('fasilitas kesehatan')
        ->assertSee('Nyeri dada')
        ->assertSee('Pingsan');
});

it('shows the pain score when the stop was caused by it', function () {
    lockPatient($this->patient, ['pain_score_high'], ['max_pain' => 9]);
    $this->actingAs($this->patientUser);

    Volt::test('pages.patient.red-flag-stop')->assertSee('Skor nyeri sangat tinggi')->assertSee('(9/10)');
});

it('shows the description of another red flag', function () {
    $check = lockPatient($this->patient, ['other']);
    $check->update(['other_text' => 'Pusing berputar hebat']);
    $this->actingAs($this->patientUser);

    Volt::test('pages.patient.red-flag-stop')->assertSee('Pusing berputar hebat');
});

it('points a clinic patient to their clinic therapist and a lone patient to the provider', function () {
    lockPatient($this->patient);
    $this->actingAs($this->patientUser);
    Volt::test('pages.patient.red-flag-stop')->assertSee('terapis di klinik Anda');

    $lone = makeUser(Role::Patient);
    lockPatient(makePatient($lone));
    $this->actingAs($lone);
    Volt::test('pages.patient.red-flag-stop')->assertSee('penyedia layanan TeleFisioRehab');
});

it('sends a patient away from the stop page when nothing is locked', function () {
    $this->actingAs($this->patientUser);

    Volt::test('pages.patient.red-flag-stop')->assertRedirect(route('patient.screening'));
});

// ---------------------------------------------------------------- the staff side

it('lists locked patients of the therapist\'s own clinic only', function () {
    lockPatient($this->patient);

    $outsider = makeUser(Role::Patient, $this->otherClinic, ['name' => 'Pasien Klinik Lain']);
    lockPatient(makePatient($outsider));

    $this->actingAs(makeUser(Role::Therapist, $this->clinic));

    Volt::test('pages.therapist.red-flag-review')
        ->assertSee('Pasien Merah')
        ->assertDontSee('Pasien Klinik Lain')
        ->assertSee('Nyeri dada');
});

it('shows an empty state when nobody is locked', function () {
    $this->actingAs(makeUser(Role::Therapist, $this->clinic));

    Volt::test('pages.therapist.red-flag-review')->assertSee('Tidak ada pasien yang sedang dihentikan');
});

it('needs a written note of at least 10 characters to unlock', function () {
    $check = lockPatient($this->patient);
    $this->actingAs(makeUser(Role::Therapist, $this->clinic));

    Volt::test('pages.therapist.red-flag-review')
        ->call('unlock', $check->id)
        ->assertHasErrors(["notes.{$check->id}"])
        ->set("notes.{$check->id}", 'pendek')
        ->call('unlock', $check->id)
        ->assertHasErrors(["notes.{$check->id}"]);

    expect($check->fresh()->isLocked())->toBeTrue();
});

it('lets a therapist of the same clinic unlock with a note, and records it', function () {
    $check = lockPatient($this->patient);
    $therapist = makeUser(Role::Therapist, $this->clinic);
    $this->actingAs($therapist);

    Volt::test('pages.therapist.red-flag-review')
        ->set("notes.{$check->id}", 'Sudah diperiksa dokter di RS, hasil normal')
        ->call('unlock', $check->id)
        ->assertHasNoErrors();

    $check = $check->fresh();
    expect($check->isLocked())->toBeFalse()
        ->and($check->unlocked_at)->not->toBeNull()
        ->and($check->unlocked_by)->toBe($therapist->id)
        ->and($check->unlock_note)->toBe('Sudah diperiksa dokter di RS, hasil normal')
        ->and($this->patient->fresh()->isRedFlagLocked())->toBeFalse();
});

it('lets a clinic admin of the same clinic unlock too', function () {
    $check = lockPatient($this->patient);
    $this->actingAs(makeUser(Role::ClinicAdmin, $this->clinic));

    Volt::test('pages.therapist.red-flag-review')
        ->set("notes.{$check->id}", 'Pasien sudah kontrol ke puskesmas')
        ->call('unlock', $check->id);

    expect($check->fresh()->isLocked())->toBeFalse();
});

it('does not let a therapist of another clinic unlock', function () {
    $check = lockPatient($this->patient);
    $this->actingAs(makeUser(Role::Therapist, $this->otherClinic));

    try {
        Volt::test('pages.therapist.red-flag-review')
            ->set("notes.{$check->id}", 'Catatan dari klinik lain')
            ->call('unlock', $check->id);
    } catch (\Throwable) {
        // a 403 or 404 is the expected outcome; what matters is that nothing changed
    }

    expect($check->fresh()->isLocked())->toBeTrue()->and($check->fresh()->unlocked_by)->toBeNull();
});

it('lets a platform admin unlock a patient without a clinic', function () {
    $lone = makeUser(Role::Patient, null, ['name' => 'Pasien Mandiri']);
    $check = lockPatient(makePatient($lone));
    $admin = makeUser(Role::PlatformAdmin);
    $this->actingAs($admin);

    Volt::test('pages.therapist.red-flag-review')
        ->assertSee('Pasien Mandiri')
        ->assertSee('Tanpa klinik (mandiri)')
        ->set("notes.{$check->id}", 'Sudah diperiksa, boleh lanjut')
        ->call('unlock', $check->id);

    expect($check->fresh()->isLocked())->toBeFalse()->and($check->fresh()->unlocked_by)->toBe($admin->id);
});

it('shows a platform admin the locked patients of every clinic', function () {
    lockPatient($this->patient);
    lockPatient(makePatient(makeUser(Role::Patient, $this->otherClinic, ['name' => 'Pasien Klinik Lain'])));

    $this->actingAs(makeUser(Role::PlatformAdmin));

    Volt::test('pages.therapist.red-flag-review')->assertSee('Pasien Merah')->assertSee('Pasien Klinik Lain');
});

it('does not list a patient again after the unlock', function () {
    $check = lockPatient($this->patient);
    $check->update(['unlocked_at' => now()]);

    $this->actingAs(makeUser(Role::Therapist, $this->clinic));

    Volt::test('pages.therapist.red-flag-review')->assertDontSee('Pasien Merah');
});

// ---------------------------------------------------------------- after the unlock

it('lets the patient in again after the unlock', function () {
    $check = lockPatient($this->patient);
    $check->update(['unlocked_at' => now(), 'unlocked_by' => makeUser(Role::Therapist, $this->clinic)->id, 'unlock_note' => 'Sudah diperiksa']);

    $this->withoutVite();
    $this->actingAs($this->patientUser)->get(route('patient.screening'))->assertOk();
});

it('starts a brand new screening after the unlock and keeps the stopped one as history', function () {
    $check = lockPatient($this->patient);
    $check->update(['unlocked_at' => now()]);
    $this->actingAs($this->patientUser);

    Volt::test('pages.patient.screening')->assertSet('step', 1)->assertSet('complaints', []);

    expect($this->patient->screenings()->where('status', 'stopped')->count())->toBe(1);
});

it('locks again when the new screening hits another red flag', function () {
    $check = lockPatient($this->patient);
    $check->update(['unlocked_at' => now()]);
    $this->actingAs($this->patientUser);

    $t = Volt::test('pages.patient.screening')->call('toggleComplaint', 'balance')->call('next')->call('next');
    $t->set('char.onset', 'kemarin')->call('setChoice', 'onsetType', 'sudden')->call('setChoice', 'dailyImpact', 'yes')->call('next');
    foreach (\App\Support\Onboarding\RedFlags::keys() as $key) {
        $t->call('setRf', $key, $key === 'fainting' ? 'yes' : 'no');
    }
    $t->call('next')->assertRedirect(route('patient.red-flag.stop'));

    expect($this->patient->fresh()->isRedFlagLocked())->toBeTrue();
});

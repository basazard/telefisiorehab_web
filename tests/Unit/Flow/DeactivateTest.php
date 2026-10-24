<?php

use App\Enums\Role;
use App\Models\PatientAccessLog;
use App\Support\Staff\Assignment;
use App\Support\Staff\Audit;
use App\Support\Staff\TeamActions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 09 S4.3: a clinic admin deactivates a therapist; their patients are moved or released first.

beforeEach(function () {
    $this->withoutVite();

    $this->clinic = makeClinic('Klinik Sehat');
    $this->admin = makeUser(Role::ClinicAdmin, $this->clinic);
    $this->ani = makeUser(Role::Therapist, $this->clinic, ['name' => 'Ani Terapis', 'email' => 'ani@klinik.id']);
    $this->budi = makeUser(Role::Therapist, $this->clinic, ['name' => 'Budi Terapis']);
    $this->p1 = staffPatient('Pasien Satu', $this->clinic, $this->ani);
    $this->p2 = staffPatient('Pasien Dua', $this->clinic, $this->ani);
});

it('moves the patients to another therapist, then deactivates', function () {
    expect(TeamActions::deactivate($this->admin, $this->ani, $this->budi))->toBe(2)
        ->and($this->p1->fresh()->therapist_id)->toBe($this->budi->id)
        ->and($this->p2->fresh()->therapist_id)->toBe($this->budi->id)
        ->and($this->ani->fresh()->isDeactivated())->toBeTrue()
        ->and(PatientAccessLog::where('action', Audit::ASSIGNED)->count())->toBe(2);
});

it('releases the patients when no therapist is chosen', function () {
    TeamActions::deactivate($this->admin, $this->ani, null);

    expect($this->p1->fresh()->therapist_id)->toBeNull()->and($this->p2->fresh()->therapist_id)->toBeNull();
});

it('deactivates from the team page', function () {
    $this->actingAs($this->admin);

    Volt::test('pages.clinic.team')->call('startDeactivate', $this->ani->id)->assertSee('2 pasien perlu dipindahkan')
        ->set('moveTo', (string) $this->budi->id)->call('deactivate')->assertHasNoErrors()->assertSee('Nonaktif');

    expect($this->ani->fresh()->isDeactivated())->toBeTrue();
});

it('locks a deactivated user out of an open session', function () {
    $this->actingAs($this->ani)->get(route('therapist.dashboard'))->assertOk();
    TeamActions::deactivate($this->admin, $this->ani, null);     // the session user (a real request reloads it)

    $this->get(route('therapist.dashboard'))->assertRedirect(route('login'))->assertSessionHas('status');
    $this->assertGuest();
});

it('refuses a deactivated user at login', function () {
    TeamActions::deactivate($this->admin, $this->ani, null);

    Volt::test('pages.auth.login')->set('form.email', 'ani@klinik.id')->set('form.password', 'password')->call('login')
        ->assertHasErrors('form.email');

    $this->assertGuest();
});

it('never deactivates the admin themselves, another admin or another clinic', function () {
    $otherAdmin = makeUser(Role::ClinicAdmin, $this->clinic);
    $foreign = makeUser(Role::Therapist, makeClinic('Klinik Lain'));

    foreach ([$this->admin, $otherAdmin, $foreign] as $target) {
        expect(fn () => TeamActions::deactivate($this->admin, $target, null))->toThrow(AuthorizationException::class)
            ->and($target->fresh()->isDeactivated())->toBeFalse();
    }

    expect($this->ani->can('deactivate', $this->budi))->toBeFalse();     // therapists cannot
});

it('refuses a deactivated therapist as the new therapist of the patients', function () {
    $this->budi->forceFill(['deactivated_at' => now()])->save();

    expect(fn () => TeamActions::deactivate($this->admin, $this->ani, $this->budi))->toThrow(AuthorizationException::class)
        ->and($this->ani->fresh()->isDeactivated())->toBeFalse()
        ->and($this->p1->fresh()->therapist_id)->toBe($this->ani->id);          // rolled back
});

it('never assigns to or lets a deactivated therapist claim', function () {
    $this->budi->forceFill(['deactivated_at' => now()])->save();
    $free = staffPatient('Pasien Bebas', $this->clinic);

    expect(fn () => Assignment::assign($this->admin, $free, $this->budi))->toThrow(AuthorizationException::class)
        ->and($this->budi->can('claim', $free))->toBeFalse();
});

it('reactivates a therapist', function () {
    TeamActions::deactivate($this->admin, $this->ani, null);
    $this->actingAs($this->admin);

    Volt::test('pages.clinic.team')->call('reactivate', $this->ani->id)->assertHasNoErrors();

    expect($this->ani->fresh()->isDeactivated())->toBeFalse();
});

<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

it('sends guests to the login page', function () {
    foreach (['patient.dashboard', 'patient.screening', 'patient.profile.setup', 'staff.red-flags'] as $name) {
        $this->get(route($name))->assertRedirect(route('login'));
    }
});

it('sends a patient without a finished profile to the profile setup', function () {
    $user = makeUser();
    makePatient($user, complete: false);
    $this->actingAs($user);

    foreach (['patient.dashboard', 'patient.screening', 'patient.domain'] as $name) {
        $this->get(route($name))->assertRedirect(route('patient.profile.setup'));
    }
});

it('keeps staff out of the patient pages', function () {
    foreach ([Role::Therapist, Role::ClinicAdmin, Role::PlatformAdmin] as $role) {
        $this->actingAs(makeUser($role, makeClinic()))
            ->get(route('patient.profile.setup'))
            ->assertForbidden();
    }
});

it('keeps patients out of the staff red-flag page', function () {
    $user = makeUser();
    makePatient($user);

    $this->actingAs($user)->get(route('staff.red-flags'))->assertForbidden();
});

it('sends each role to its own home from /dashboard', function () {
    foreach (Role::cases() as $role) {
        $user = makeUser($role, $role === Role::PlatformAdmin ? null : makeClinic());

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route($role->home()));
    }
});

it('does not run the profile gate for staff', function () {
    $this->actingAs(makeUser(Role::Therapist, makeClinic()))
        ->get(route('patient.dashboard'))
        ->assertForbidden();                      // role check comes first, not a redirect to the profile setup
});

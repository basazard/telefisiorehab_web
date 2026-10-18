<?php

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->clinic = makeClinic('Klinik A');
    $this->otherClinic = makeClinic('Klinik B');

    $this->owner = makeUser(Role::Patient, $this->clinic);
    $this->patient = makePatient($this->owner);
    $this->screening = makeScreening($this->patient);
});

it('lets the owner patient view and edit an in-progress screening', function () {
    expect($this->owner->can('view', $this->screening))->toBeTrue()
        ->and($this->owner->can('update', $this->screening))->toBeTrue();
});

it('keeps other patients out of a screening', function () {
    $other = makeUser(Role::Patient, $this->clinic);
    makePatient($other);

    expect($other->can('view', $this->screening))->toBeFalse()
        ->and($other->can('update', $this->screening))->toBeFalse();
});

it('does not let anyone edit a finished screening', function () {
    $this->screening->update(['status' => 'completed']);

    expect($this->owner->can('update', $this->screening->fresh()))->toBeFalse();
});

it('lets only staff of the right scope view a screening', function () {
    $assigned = makeUser(Role::Therapist, $this->clinic);
    $notAssigned = makeUser(Role::Therapist, $this->clinic);
    $admin = makeUser(Role::ClinicAdmin, $this->clinic);
    $outsideAdmin = makeUser(Role::ClinicAdmin, $this->otherClinic);
    $platform = makeUser(Role::PlatformAdmin);

    $this->patient->update(['therapist_id' => $assigned->id]);
    $screening = $this->screening->fresh();

    expect($assigned->can('view', $screening))->toBeTrue()
        ->and($notAssigned->can('view', $screening))->toBeFalse()
        ->and($admin->can('view', $screening))->toBeTrue()
        ->and($outsideAdmin->can('view', $screening))->toBeFalse()
        ->and($platform->can('view', $screening))->toBeTrue();
});

it('never lets staff edit a screening', function () {
    expect(makeUser(Role::Therapist, $this->clinic)->can('update', $this->screening))->toBeFalse()
        ->and(makeUser(Role::PlatformAdmin)->can('update', $this->screening))->toBeFalse();
});

it('lets only staff of the same clinic, or a platform admin, unlock a red-flag stop', function () {
    $check = lockPatient($this->patient);

    expect(makeUser(Role::Therapist, $this->clinic)->can('unlock', $check))->toBeTrue()
        ->and(makeUser(Role::ClinicAdmin, $this->clinic)->can('unlock', $check))->toBeTrue()
        ->and(makeUser(Role::PlatformAdmin)->can('unlock', $check))->toBeTrue()
        ->and(makeUser(Role::Therapist, $this->otherClinic)->can('unlock', $check))->toBeFalse()
        ->and(makeUser(Role::ClinicAdmin, $this->otherClinic)->can('unlock', $check))->toBeFalse()
        ->and(makeUser(Role::Therapist)->can('unlock', $check))->toBeFalse()      // therapist without a clinic
        ->and($this->owner->can('unlock', $check))->toBeFalse();                  // the patient themselves
});

it('lets only a platform admin unlock a patient without a clinic', function () {
    $loneUser = makeUser(Role::Patient);                       // direct-to-consumer, no clinic
    $check = lockPatient(makePatient($loneUser));

    expect(makeUser(Role::PlatformAdmin)->can('unlock', $check))->toBeTrue()
        ->and(makeUser(Role::Therapist, $this->clinic)->can('unlock', $check))->toBeFalse()
        ->and(makeUser(Role::ClinicAdmin, $this->clinic)->can('unlock', $check))->toBeFalse();
});

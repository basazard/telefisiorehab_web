<?php

use App\Enums\Role;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 09 §4, the S1 cells: list, open the detail, claim, assign. Two clinics to prove isolation.

beforeEach(function () {
    $this->clinicA = makeClinic('Klinik A');
    $this->clinicB = makeClinic('Klinik B');

    $this->ani = makeUser(Role::Therapist, $this->clinicA);
    $this->budi = makeUser(Role::Therapist, $this->clinicA);
    $this->adminA = makeUser(Role::ClinicAdmin, $this->clinicA);
    $this->citra = makeUser(Role::Therapist, $this->clinicB);
    $this->adminB = makeUser(Role::ClinicAdmin, $this->clinicB);
    $this->platform = makeUser(Role::PlatformAdmin);

    $this->anisPatient = makePatient(makeUser(clinic: $this->clinicA), ['therapist_id' => $this->ani->id]);
    $this->unassigned = makePatient(makeUser(clinic: $this->clinicA));
    $this->patientUser = makeUser(clinic: $this->clinicA);
    makePatient($this->patientUser);
});

it('lets staff list patients, never patients', function () {
    foreach (['ani', 'adminA', 'platform'] as $who) {
        expect($this->{$who}->can('viewAny', Patient::class))->toBeTrue();
    }

    expect($this->patientUser->can('viewAny', Patient::class))->toBeFalse();
});

it('opens a detail only for the assigned therapist, the clinic admin and the platform admin', function () {
    $p = $this->anisPatient;

    expect($this->ani->can('view', $p))->toBeTrue()
        ->and($this->budi->can('view', $p))->toBeFalse()          // same clinic, not assigned
        ->and($this->adminA->can('view', $p))->toBeTrue()
        ->and($this->citra->can('view', $p))->toBeFalse()         // other clinic
        ->and($this->adminB->can('view', $p))->toBeFalse()
        ->and($this->platform->can('view', $p))->toBeTrue()
        ->and($this->patientUser->can('view', $p))->toBeFalse();
});

it('keeps the detail of an unassigned patient closed to therapists until they claim it', function () {
    expect($this->ani->can('view', $this->unassigned))->toBeFalse()
        ->and($this->adminA->can('view', $this->unassigned))->toBeTrue();
});

it('lets a therapist of the same clinic claim an unassigned patient', function () {
    expect($this->ani->can('claim', $this->unassigned))->toBeTrue()
        ->and($this->budi->can('claim', $this->anisPatient))->toBeFalse()   // already assigned
        ->and($this->citra->can('claim', $this->unassigned))->toBeFalse()   // other clinic
        ->and($this->adminA->can('claim', $this->unassigned))->toBeFalse()  // admins assign, they do not claim
        ->and($this->platform->can('claim', $this->unassigned))->toBeFalse()
        ->and($this->patientUser->can('claim', $this->unassigned))->toBeFalse();
});

it('lets the clinic admin and the platform admin assign, not therapists', function () {
    expect($this->adminA->can('assign', $this->unassigned))->toBeTrue()
        ->and($this->platform->can('assign', $this->unassigned))->toBeTrue()
        ->and($this->adminB->can('assign', $this->unassigned))->toBeFalse()
        ->and($this->ani->can('assign', $this->unassigned))->toBeFalse()
        ->and($this->patientUser->can('assign', $this->unassigned))->toBeFalse();
});

it('only assigns to a therapist of the patient clinic', function () {
    $p = $this->unassigned;

    expect($this->adminA->can('assign', [$p, $this->budi]))->toBeTrue()
        ->and($this->adminA->can('assign', [$p, $this->citra]))->toBeFalse()       // therapist of another clinic
        ->and($this->adminA->can('assign', [$p, $this->adminA]))->toBeFalse()      // not a therapist
        ->and($this->platform->can('assign', [$p, $this->citra]))->toBeFalse();
});

it('keeps patients without a clinic away from clinic staff', function () {
    $own = makePatient(makeUser());

    expect($this->adminA->can('view', $own))->toBeFalse()
        ->and($this->ani->can('claim', $own))->toBeFalse()
        ->and($this->platform->can('view', $own))->toBeTrue()
        ->and($this->platform->can('assign', [$own, $this->ani]))->toBeFalse();
});

it('keeps the unused patient abilities denied', function () {
    foreach (['update', 'delete', 'restore', 'forceDelete'] as $ability) {
        expect($this->platform->can($ability, $this->anisPatient))->toBeFalse();
    }

    expect($this->platform->can('create', Patient::class))->toBeFalse();
});

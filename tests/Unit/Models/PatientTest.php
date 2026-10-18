<?php

use App\Enums\Gender;
use App\Enums\Role;
use App\Enums\ServiceModule;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

it('calculates age and service module from the date of birth', function () {
    $child = (new Patient)->forceFill(['date_of_birth' => now()->subYears(10)]);
    $adult = (new Patient)->forceFill(['date_of_birth' => now()->subYears(30)]);
    $senior = (new Patient)->forceFill(['date_of_birth' => now()->subYears(65)]);

    expect($child->age)->toBe(10)->and($child->module)->toBe(ServiceModule::Pediatric)
        ->and($adult->age)->toBe(30)->and($adult->module)->toBe(ServiceModule::Adult)
        ->and($senior->age)->toBe(65)->and($senior->module)->toBe(ServiceModule::Geriatric);
});

it('changes module exactly on the 18th and 60th birthday', function () {
    expect((new Patient)->forceFill(['date_of_birth' => now()->subYears(17)->subDay()])->module)->toBe(ServiceModule::Pediatric)
        ->and((new Patient)->forceFill(['date_of_birth' => now()->subYears(18)])->module)->toBe(ServiceModule::Adult)
        ->and((new Patient)->forceFill(['date_of_birth' => now()->subYears(59)])->module)->toBe(ServiceModule::Adult)
        ->and((new Patient)->forceFill(['date_of_birth' => now()->subYears(60)])->module)->toBe(ServiceModule::Geriatric);
});

it('has no age or module without a date of birth', function () {
    $patient = new Patient;

    expect($patient->age)->toBeNull()->and($patient->module)->toBeNull();
});

it('casts gender, conditions and goals to enums', function () {
    $user = makeUser();
    $patient = makePatient($user, ['special_conditions' => ['pregnancy', 'diabetes'], 'goals' => ['reduce_pain', 'balance']])->fresh();

    expect($patient->gender)->toBe(Gender::Female)
        ->and($patient->special_conditions->map->value->all())->toBe(['pregnancy', 'diabetes'])
        ->and($patient->goals->map->value->all())->toBe(['reduce_pain', 'balance']);
});

it('knows whether the profile is complete', function () {
    $complete = makePatient(makeUser());
    $incomplete = makePatient(makeUser(), complete: false);

    expect($complete->isProfileComplete())->toBeTrue()
        ->and($incomplete->isProfileComplete())->toBeFalse();
});

it('is locked only while a red-flag stop is active', function () {
    $patient = makePatient(makeUser());
    expect($patient->isRedFlagLocked())->toBeFalse();

    $check = lockPatient($patient);
    expect($patient->isRedFlagLocked())->toBeTrue();

    $check->update(['unlocked_at' => now()]);
    expect($patient->isRedFlagLocked())->toBeFalse();
});

it('belongs to its user and exposes its screenings', function () {
    $user = makeUser();
    $patient = makePatient($user);
    makeScreening($patient);
    makeScreening($patient, ['status' => 'completed']);

    expect($patient->user->is($user))->toBeTrue()
        ->and($patient->screenings)->toHaveCount(2)
        ->and($user->patient->is($patient))->toBeTrue();
});

<?php

use App\Enums\Role;
use App\Models\RedFlagCheck;
use App\Models\Screening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

it('stores arrays as JSON and reads them back', function () {
    $patient = makePatient(makeUser());
    $screening = makeScreening($patient, [
        'regions' => ['Left Knee', 'Lower Back'],
        'pain_levels' => ['Left Knee' => 4, 'Lower Back' => 7],
        'characteristics' => ['onset' => '2 minggu lalu', 'aggravators' => ['sitting']],
        'special_groups' => [['key' => 'adult', 'status' => 'pending']],
    ])->fresh();

    expect($screening->regions)->toBe(['Left Knee', 'Lower Back'])
        ->and($screening->pain_levels)->toBe(['Left Knee' => 4, 'Lower Back' => 7])
        ->and($screening->characteristics['aggravators'])->toBe(['sitting'])
        ->and($screening->special_groups[0]['status'])->toBe('pending');
});

it('tracks in progress, stopped and completed status', function () {
    $patient = makePatient(makeUser());
    $inProgress = makeScreening($patient);
    $stopped = makeScreening($patient, ['status' => 'stopped']);
    $completed = makeScreening($patient, ['status' => 'completed', 'completed_at' => now()]);

    expect($inProgress->isCompleted())->toBeFalse()
        ->and($stopped->isStopped())->toBeTrue()
        ->and($completed->isCompleted())->toBeTrue()
        ->and($completed->completed_at)->not->toBeNull();

    $ids = Screening::query()->inProgress()->pluck('id')->all();
    expect($ids)->toBe([$inProgress->id]);
});

it('keeps each screening as its own history row', function () {
    $patient = makePatient(makeUser());
    makeScreening($patient, ['status' => 'completed']);
    makeScreening($patient);

    expect($patient->screenings()->count())->toBe(2);
});

it('knows its red-flag check', function () {
    $patient = makePatient(makeUser());
    $check = lockPatient($patient, ['chest_pain', 'fainting']);

    expect($check->screening->redFlagCheck->is($check))->toBeTrue()
        ->and($check->flagged)->toBe(['chest_pain', 'fainting'])
        ->and($check->flaggedLabels())->toBe(['Nyeri dada atau dada terasa tertekan', 'Pingsan atau hilang kesadaran']);
});

it('is locked until a clinic user unlocks it', function () {
    $check = new RedFlagCheck;
    expect($check->isLocked())->toBeFalse();

    $check->stopped_at = now();
    expect($check->isLocked())->toBeTrue();

    $check->unlocked_at = now();
    expect($check->isLocked())->toBeFalse();
});

it('records who unlocked it and why', function () {
    $clinic = makeClinic();
    $therapist = makeUser(Role::Therapist, $clinic);
    $check = lockPatient(makePatient(makeUser(Role::Patient, $clinic)));

    $check->update(['unlocked_at' => now(), 'unlocked_by' => $therapist->id, 'unlock_note' => 'Sudah diperiksa dokter']);

    expect($check->fresh()->unlockedBy->is($therapist))->toBeTrue()
        ->and($check->fresh()->unlock_note)->toBe('Sudah diperiksa dokter');
});

it('shows each clinic only its own screenings', function () {
    $a = makeClinic('Klinik A');
    $b = makeClinic('Klinik B');
    $therapistA = makeUser(Role::Therapist, $a);
    $admin = makeUser(Role::PlatformAdmin);

    makeScreening(makePatient(makeUser(Role::Patient, $a)));
    makeScreening(makePatient(makeUser(Role::Patient, $b)));

    $this->actingAs($therapistA);
    expect(Screening::count())->toBe(1);

    $this->actingAs($admin);
    expect(Screening::count())->toBe(2);
});

it('stamps the clinic of the signed-in user on new records', function () {
    $clinic = makeClinic();
    $user = makeUser(Role::Patient, $clinic);
    $patient = makePatient($user);

    $this->actingAs($user);
    $screening = $patient->screenings()->create([
        'regions' => [], 'pain_levels' => [], 'max_pain' => 0, 'status' => 'in_progress',
    ]);

    expect($screening->clinic_id)->toBe($clinic->id);
});

it('only returns a patient their own screenings through the relation', function () {
    $mine = makePatient(makeUser());
    $other = makePatient(makeUser());
    makeScreening($mine);
    makeScreening($other);
    makeScreening($other);

    expect($mine->screenings()->count())->toBe(1)->and($other->screenings()->count())->toBe(2);
});

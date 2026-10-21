<?php

use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\ExerciseLog;
use App\Support\Onboarding\Programs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->user = makeUser(Role::Patient, makeClinic());
    $this->patient = makePatient($this->user);
    $this->actingAs($this->user);
});

it('runs the app in Asia/Jakarta', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(now()->timezoneName)->toBe('Asia/Jakarta');
});

// Regression: with the app in UTC the exercise day rolled over at 07:00 WIB, so an exercise done just after
// midnight in Jakarta counted for the previous day.
it('counts an exercise done just after midnight WIB as done today', function () {
    $this->travelTo(Carbon::parse('2026-10-21 00:30', 'Asia/Jakarta'));         // = 2026-10-20 17:30 UTC
    $program = Programs::build(stratified(RiskTier::Low));
    $item = $program->items->first();

    ExerciseLog::forceCreate([
        'patient_id' => $this->patient->id,
        'program_exercise_id' => $item->id,
        'performed_at' => Carbon::parse('2026-10-21 00:10', 'Asia/Jakarta'),
    ]);

    expect(today()->toDateString())->toBe('2026-10-21');

    Volt::test('pages.patient.program')
        ->assertSet('doneToday', [$item->id])
        ->assertSee('Selesai hari ini');
});

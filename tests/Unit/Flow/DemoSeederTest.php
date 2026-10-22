<?php

use App\Enums\ProgramStatus;
use App\Enums\Role;
use App\Models\Clinic;
use App\Models\ExerciseLog;
use App\Models\Patient;
use App\Models\Program;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates the demo cast', function () {
    $this->seed(DemoSeeder::class);

    $a = Clinic::where('slug', 'klinik-sehat-jakarta')->firstOrFail();
    $b = Clinic::where('slug', 'klinik-bugar-bandung')->firstOrFail();

    expect(User::where('email', 'admin@demo.test')->value('role'))->toBe(Role::PlatformAdmin)
        ->and(User::where('clinic_id', $a->id)->where('role', Role::Therapist)->pluck('email')->sort()->values()->all())
            ->toBe(['ani.a@demo.test', 'budi.a@demo.test'])
        ->and(Patient::where('clinic_id', $a->id)->count())->toBe(14)
        ->and(Patient::where('clinic_id', $b->id)->count())->toBe(3)
        ->and(Patient::whereNull('clinic_id')->count())->toBe(2)
        ->and(Patient::where('clinic_id', $a->id)->whereNull('therapist_id')->count())->toBe(3);

    expect(Hash::check('password', User::where('email', 'ani.a@demo.test')->value('password')))->toBeTrue()
        ->and(User::whereNull('email_verified_at')->count())->toBe(0);
});

it('builds believable programs and logs', function () {
    $this->seed(DemoSeeder::class);

    $patient = fn (string $email) => Patient::whereRelation('user', 'email', $email)->firstOrFail();

    expect($patient('pasien09.a@demo.test')->currentProgram()->status)->toBe(ProgramStatus::NeedsReview)
        ->and($patient('pasien08.a@demo.test')->currentProgram()->next_evaluation_on->isPast())->toBeTrue()
        ->and($patient('pasien12.a@demo.test')->currentProgram()->isMaintenance())->toBeTrue()
        ->and($patient('pasien13.a@demo.test')->programs()->count())->toBe(0)
        ->and($patient('pasien04.a@demo.test')->isRedFlagLocked())->toBeTrue()
        ->and(ExerciseLog::where('performed_at', '<', now()->subDays(29))->count())->toBe(0)
        ->and($patient('pasien05.a@demo.test')->exerciseLogs()->count())->toBeGreaterThan(0);
});

it('runs twice without duplicates', function () {
    $this->seed(DemoSeeder::class);
    $counts = [User::count(), Patient::count(), Program::count(), ExerciseLog::count()];

    $this->seed(DemoSeeder::class);

    expect([User::count(), Patient::count(), Program::count(), ExerciseLog::count()])->toBe($counts);
});

it('refuses to run in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app(DemoSeeder::class)->setContainer(app())->__invoke())->toThrow(RuntimeException::class, 'production');
    expect(User::count())->toBe(0);
});

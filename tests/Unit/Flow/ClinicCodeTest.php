<?php

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 11 E3: the clinic code at registration and the registration modes.

beforeEach(function () {
    $this->withoutVite();
    config(['pilot.registration_mode' => 'clinic_code']);
    $this->clinic = makeClinic('Klinik Sehat');
});

function registerWith(?string $code, string $email = 'budi@contoh.id')
{
    return Volt::test('pages.auth.register')
        ->set('clinicCode', (string) $code)
        ->set('name', 'Budi Pasien')->set('email', $email)
        ->set('password', 'password')->set('password_confirmation', 'password')
        ->call('register');
}

// ---------------------------------------------------------------- codes

it('gives every clinic a unique, readable code', function () {
    $codes = collect(range(1, 20))->map(fn ($i) => makeClinic("Klinik $i")->join_code);

    expect($codes->unique())->toHaveCount(20)
        ->and($codes->every(fn ($c) => preg_match('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/', $c) === 1))->toBeTrue()
        ->and($this->clinic->join_code)->not->toBeNull();
});

it('gives existing clinics a code when migrating', function () {
    DB::table('clinics')->update(['join_code' => null]);
    $migration = require database_path('migrations/2026_11_13_000001_add_join_code_to_clinics.php');
    Schema::table('clinics', fn ($t) => $t->dropUnique(['join_code']));
    Schema::table('clinics', fn ($t) => $t->dropColumn('join_code'));

    $migration->up();

    expect(Clinic::whereNull('join_code')->count())->toBe(0);
});

it('finds a clinic by its code, whatever the case or spaces', function () {
    $code = $this->clinic->join_code;

    expect(Clinic::findByJoinCode(strtolower($code))?->id)->toBe($this->clinic->id)
        ->and(Clinic::findByJoinCode(' '.substr($code, 0, 4).' '.substr($code, 4).' ')?->id)->toBe($this->clinic->id)
        ->and(Clinic::findByJoinCode('ZZZZZZZZ'))->toBeNull()
        ->and(Clinic::findByJoinCode(''))->toBeNull();

    $this->clinic->update(['status' => 'inactive']);
    expect(Clinic::findByJoinCode($code))->toBeNull();
});

// ---------------------------------------------------------------- registration modes

it('requires a valid clinic code and links the patient to the clinic', function () {
    registerWith('')->assertHasErrors(['clinicCode' => 'required']);
    registerWith('ZZZZZZZZ')->assertHasErrors('clinicCode');
    expect(User::count())->toBe(0);

    registerWith(strtolower($this->clinic->join_code))->assertHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'budi@contoh.id')->sole();
    expect($user->role)->toBe(Role::Patient)
        ->and($user->clinic_id)->toBe($this->clinic->id)
        ->and($user->patient->clinic_id)->toBe($this->clinic->id);
});

it('fills the code from the link the clinic shares', function () {
    expect($this->clinic->registerUrl())->toContain('clinic='.$this->clinic->join_code);

    $this->get($this->clinic->registerUrl())->assertOk()->assertSee('clinicCode&quot;:&quot;'.$this->clinic->join_code, false);   // the field's value in the Livewire snapshot
});

it('makes the code optional in open mode', function () {
    config(['pilot.registration_mode' => 'open']);

    registerWith('', 'tanpa@contoh.id')->assertHasNoErrors();
    registerWith($this->clinic->join_code, 'dengan@contoh.id')->assertHasNoErrors();
    registerWith('SALAHKOD', 'salah@contoh.id')->assertHasErrors('clinicCode');

    expect(User::where('email', 'tanpa@contoh.id')->sole()->clinic_id)->toBeNull()
        ->and(User::where('email', 'dengan@contoh.id')->sole()->patient->clinic_id)->toBe($this->clinic->id);
});

it('closes registration and hides the links, while invitations still work', function () {
    config(['pilot.registration_mode' => 'closed']);

    $this->get(route('register'))->assertOk()->assertSee('Pendaftaran ditutup')->assertDontSee('Buat akun Anda');
    registerWith($this->clinic->join_code)->assertForbidden();
    expect(User::count())->toBe(0);

    $this->get('/')->assertDontSee(route('register'));
    $this->get(route('login'))->assertDontSee('Belum punya akun?');

    $token = \Illuminate\Support\Str::afterLast(
        App\Support\Staff\Invitations::invite(makeUser(Role::ClinicAdmin, $this->clinic), $this->clinic, 'terapis@contoh.id')['url'], '/');
    $this->get(route('invitation.accept', $token))->assertOk()->assertSee('Bergabung dengan Klinik Sehat');
});

it('shows the register links when registration is allowed', function () {
    $this->get('/')->assertSee(route('register'));
    $this->get(route('login'))->assertSee('Belum punya akun?');
});

it('limits registrations per IP (DRAFT), counting wrong codes too', function () {
    foreach (range(1, 10) as $i) {
        registerWith('SALAHKOD', "orang$i@contoh.id")->assertHasErrors('clinicCode');
    }

    registerWith($this->clinic->join_code, 'orang11@contoh.id')->assertHasErrors('email');
    expect(User::count())->toBe(0);
});

// ---------------------------------------------------------------- team page

it('shows the clinic admin the code and lets them make a new one', function () {
    $this->actingAs(makeUser(Role::ClinicAdmin, $this->clinic));
    $old = $this->clinic->join_code;

    Volt::test('pages.clinic.team')->assertSee($old)->assertSee($this->clinic->registerUrl())
        ->call('regenerateCode')->assertHasNoErrors()->assertDontSee($old);

    expect($this->clinic->fresh()->join_code)->not->toBe($old)
        ->and(Clinic::findByJoinCode($old))->toBeNull();
});

it('lets only the clinic own admin make a new code', function () {
    $other = makeClinic('Klinik Lain');

    expect(makeUser(Role::ClinicAdmin, $this->clinic)->can('regenerateCode', $this->clinic))->toBeTrue()
        ->and(makeUser(Role::ClinicAdmin, $other)->can('regenerateCode', $this->clinic))->toBeFalse()
        ->and(makeUser(Role::Therapist, $this->clinic)->can('regenerateCode', $this->clinic))->toBeFalse()
        ->and(makeUser(Role::PlatformAdmin)->can('regenerateCode', $this->clinic))->toBeFalse();
});

// ---------------------------------------------------------------- the patient reaches the clinic

it('puts a patient who registered with the code in the clinic therapist list', function () {
    $therapist = makeUser(Role::Therapist, $this->clinic);
    registerWith($this->clinic->join_code)->assertHasNoErrors();

    $this->actingAs($therapist)->get(route('therapist.patients'))->assertOk()->assertSee('Budi Pasien');
    expect(Patient::sole()->therapist_id)->toBeNull();      // unassigned until claimed
});

<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

// ---------------------------------------------------------------- pages render

it('renders the welcome page for guests with the login and register links', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('dipandu oleh tubuh Anda')
        ->assertSee('Mulai sebagai pasien')
        ->assertSee(route('login'), false)
        ->assertSee(route('register'), false);
});

it('renders the welcome page for a signed-in user with a link to the dashboard', function () {
    $this->actingAs(makeUser())->get('/')
        ->assertOk()
        ->assertSee('Buka dasbor')
        ->assertDontSee('Mulai sebagai pasien');
});

it('renders the login, register and forgot-password pages in the new design', function () {
    $this->get(route('login'))->assertOk()->assertSee('Selamat datang kembali')->assertSee('Lupa kata sandi?')->assertSee('Tampilkan kata sandi', false);
    $this->get(route('register'))->assertOk()->assertSee('Buat akun Anda')->assertSee('Nama lengkap');
    $this->get(route('password.request'))->assertOk()->assertSee('Lupa kata sandi?')->assertSee('Kirim tautan reset');
});

it('renders the reset-password page', function () {
    $this->get(route('password.reset', ['token' => 'abc123']))->assertOk()->assertSee('Buat kata sandi baru');
});

it('renders the confirm-password page for a signed-in user', function () {
    $this->actingAs(makeUser())->get(route('password.confirm'))->assertOk()->assertSee('Konfirmasi kata sandi');
});

it('renders the verify-email page for an unverified user', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertSee('Verifikasi email Anda')->assertSee('Kirim ulang email verifikasi');
});

it('shows the brand panel on the guest layout', function () {
    $this->get(route('login'))->assertSee('TeleFisioRehab')->assertSee('Dipantau fisioterapis dan klinik Anda');
});

// ---------------------------------------------------------------- the logic behind the new markup still works

it('still logs in with the new login form', function () {
    $user = User::factory()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

it('still rejects a wrong password', function () {
    $user = User::factory()->create();

    Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'wrong-password')
        ->call('login')
        ->assertHasErrors();

    $this->assertGuest();
});

it('still registers a new account as a patient', function () {
    Volt::test('pages.auth.register')
        ->set('name', 'Budi Santoso')
        ->set('email', 'budi@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'budi@example.com')->first();

    expect($user)->not->toBeNull()->and($user->role)->toBe(Role::Patient);
    $this->assertAuthenticated();
});

it('validates the register form', function () {
    Volt::test('pages.auth.register')
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->set('password', 'short')
        ->set('password_confirmation', 'different')
        ->call('register')
        ->assertHasErrors(['name', 'email', 'password']);
});

it('logs out with the new logout button', function () {
    $this->actingAs(makeUser());

    Volt::test('layout.logout-button')->call('logout')->assertRedirect('/');

    $this->assertGuest();
});

// ---------------------------------------------------------------- profile pages

it('renders the profile page with the three forms', function () {
    $user = makeUser();
    makePatient($user);

    $this->actingAs($user)->get('/profile')
        ->assertOk()
        ->assertSee('Profil akun')
        ->assertSeeVolt('profile.update-profile-information-form')
        ->assertSeeVolt('profile.update-password-form')
        ->assertSeeVolt('profile.delete-user-form');
});

it('links patients to their health profile but not staff', function () {
    $patient = makeUser();
    makePatient($patient);
    $this->actingAs($patient)->get('/profile')->assertSee('Perbarui data kesehatan')->assertSee(route('patient.profile.setup'), false);

    $this->actingAs(makeUser(Role::Therapist, makeClinic()))->get('/profile')->assertOk()->assertDontSee('Perbarui data kesehatan');
});

it('updates the account information', function () {
    $user = makeUser();
    $this->actingAs($user);

    Volt::test('profile.update-profile-information-form')
        ->set('name', 'Nama Baru')
        ->set('email', 'baru@example.com')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Nama Baru')->and($user->fresh()->email)->toBe('baru@example.com');
});

it('updates the password', function () {
    $user = makeUser();
    $this->actingAs($user);

    Volt::test('profile.update-password-form')
        ->set('current_password', 'password')
        ->set('password', 'kata-sandi-baru')
        ->set('password_confirmation', 'kata-sandi-baru')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(\Illuminate\Support\Facades\Hash::check('kata-sandi-baru', $user->fresh()->password))->toBeTrue();
});

it('needs the right password to delete the account', function () {
    $user = makeUser();
    $this->actingAs($user);

    Volt::test('profile.delete-user-form')->set('password', 'salah')->call('deleteUser')->assertHasErrors(['password']);
    expect($user->fresh())->not->toBeNull();

    Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')->assertHasNoErrors()->assertRedirect('/');
    expect($user->fresh())->toBeNull();
});

// ---------------------------------------------------------------- navigation: logout and profile on every layout

it('offers the profile link and logout in the dashboard shell', function () {
    $this->actingAs(makeUser(Role::Therapist, makeClinic()))->get(route('staff.red-flags'))
        ->assertOk()
        ->assertSee('Profil akun')
        ->assertSee('Keluar');
});

it('offers logout in the onboarding layout', function () {
    $user = makeUser();
    makePatient($user, complete: false);

    $this->actingAs($user)->get(route('patient.profile.setup'))->assertOk()->assertSee('Keluar');
});

// ---------------------------------------------------------------- shared components

it('renders the restyled form components', function () {
    $this->blade('<x-text-input name="a" />')->assertSee('rounded-xl', false);
    $this->blade('<x-text-input name="a" :disabled="true" />')->assertSee('disabled', false);
    $this->blade('<x-input-label value="Nama" />')->assertSee('Nama');
    $this->blade('<x-password-input name="p" id="p" />')->assertSee('type="password"', false)->assertSee('Tampilkan kata sandi', false);
});

it('keeps the Breeze button defaults', function () {
    $this->blade('<x-primary-button>Kirim</x-primary-button>')->assertSee('type="submit"', false)->assertSee('Kirim');
    $this->blade('<x-secondary-button>Batal</x-secondary-button>')->assertSee('type="button"', false);
    $this->blade('<x-danger-button>Hapus</x-danger-button>')->assertSee('type="submit"', false)->assertSee('bg-destructive', false);
});

it('lists validation errors and hides an empty list', function () {
    $this->blade('<x-input-error :messages="[\'Email wajib diisi\', \'Format salah\']" />')->assertSee('Email wajib diisi')->assertSee('Format salah');
    $this->blade('<x-input-error :messages="[]" />')->assertDontSee('<ul', false);
});

it('shows a status message only when there is one', function () {
    $this->blade('<x-auth-session-status status="Tautan reset sudah dikirim" />')->assertSee('Tautan reset sudah dikirim');
    $this->blade('<x-auth-session-status :status="null" />')->assertDontSee('border-success', false);
});

it('renders the modal with the Breeze event names', function () {
    $this->blade('<x-modal name="konfirmasi">Isi modal</x-modal>')
        ->assertSee('Isi modal')
        ->assertSee('open-modal.window', false)
        ->assertSee('close-modal.window', false)
        ->assertSee('display: none', false);

    $this->blade('<x-modal name="konfirmasi" :show="true">Terbuka</x-modal>')->assertDontSee('display: none', false);
});

it('renders the brand logo', function () {
    $this->blade('<x-application-logo />')->assertSee('gradient-hero', false);
});

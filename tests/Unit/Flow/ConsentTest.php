<?php

use App\Enums\Role;
use App\Http\Middleware\EnsureConsent;
use App\Models\Consent;
use App\Models\User;
use App\Support\Legal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 11 E4: consent gate, privacy and terms pages, data request section.

beforeEach(function () {
    $this->withoutVite();
    $this->clinic = makeClinic();
});

function patientWithoutConsent(): User
{
    $user = User::factory()->withoutConsent()->create(['role' => Role::Patient, 'clinic_id' => test()->clinic->id]);
    makePatient($user);

    return $user;
}

// ---------------------------------------------------------------- the gate

it('sends a patient and every staff role to the consent page first', function () {
    $users = [
        'patient.dashboard' => patientWithoutConsent(),
        'therapist.dashboard' => User::factory()->withoutConsent()->create(['role' => Role::Therapist, 'clinic_id' => $this->clinic->id]),
        'clinic.dashboard' => User::factory()->withoutConsent()->create(['role' => Role::ClinicAdmin, 'clinic_id' => $this->clinic->id]),
        'staff.red-flags' => User::factory()->withoutConsent()->create(['role' => Role::PlatformAdmin]),
    ];

    foreach ($users as $home => $user) {
        $this->actingAs($user)->get(route($home))->assertRedirect(route('consent'));
        $this->get(route('profile'))->assertRedirect(route('consent'));
    }
});

it('keeps logout, privacy, terms and the consent page itself reachable', function () {
    $this->actingAs(patientWithoutConsent());

    $this->get(route('consent'))->assertOk()->assertSee('Persetujuan penggunaan');
    $this->get(route('legal.privacy'))->assertOk();
    $this->get(route('legal.terms'))->assertOk();
    Volt::test('pages.consent')->call('logout')->assertRedirect('/');
    $this->assertGuest();
});

it('asks an unverified user to verify the email before consenting', function () {
    $user = User::factory()->withoutConsent()->unverified()->create();

    $this->actingAs($user)->get(route('consent'))->assertRedirect(route('verification.notice'));
});

it('checks Livewire actions on gated pages too', function () {
    expect(app(PersistentMiddleware::class)->getPersistentMiddleware())->toContain(EnsureConsent::class);
});

// ---------------------------------------------------------------- accepting

it('needs every statement ticked', function () {
    $user = patientWithoutConsent();
    $this->actingAs($user);

    Volt::test('pages.consent')->set('agreed.terms', true)->call('accept')->assertHasErrors('agreed.health_data');

    expect(Consent::count())->toBe(0);
});

it('records the version and the statements, then continues where the user was going', function () {
    $user = patientWithoutConsent();
    $this->actingAs($user)->get(route('patient.history'))->assertRedirect(route('consent'));

    Volt::test('pages.consent')->assertSee(config('legal.items.patient.health_data'))
        ->set('agreed.terms', true)->set('agreed.health_data', true)
        ->call('accept')->assertHasNoErrors()->assertRedirect(route('patient.history'));

    $consent = Consent::sole();
    expect($consent->user_id)->toBe($user->id)
        ->and($consent->version)->toBe(config('legal.consent_version'))
        ->and($consent->items)->toBe(['terms', 'health_data'])
        ->and($consent->accepted_at)->not->toBeNull();

    $this->get(route('patient.history'))->assertOk();
});

it('shows staff the confidentiality statement instead of the health-data one', function () {
    $this->actingAs(User::factory()->withoutConsent()->create(['role' => Role::Therapist, 'clinic_id' => $this->clinic->id]));

    Volt::test('pages.consent')->assertSee(config('legal.items.staff.confidentiality'))->assertDontSee(config('legal.items.patient.health_data'))
        ->set('agreed.terms', true)->set('agreed.confidentiality', true)->call('accept')->assertHasNoErrors();

    expect(Consent::sole()->items)->toBe(['terms', 'confidentiality']);
});

it('asks again after a new version, keeping the old consent', function () {
    $user = makeUser(Role::Patient, $this->clinic);              // the factory consents to the current version
    makePatient($user);
    $this->actingAs($user)->get(route('patient.history'))->assertOk();

    config(['legal.consent_version' => '2026.2']);
    $this->get(route('patient.history'))->assertRedirect(route('consent'));

    Volt::test('pages.consent')->set('agreed.terms', true)->set('agreed.health_data', true)->call('accept');
    expect(Consent::where('user_id', $user->id)->pluck('version')->all())->toBe(['2026.1-draft', '2026.2']);
});

it('never changes or deletes a consent', function () {
    $consent = Consent::where('user_id', makeUser()->id)->sole();

    expect(fn () => $consent->update(['version' => 'x']))->toThrow(LogicException::class)
        ->and(fn () => $consent->delete())->toThrow(LogicException::class);
});

// ---------------------------------------------------------------- privacy and terms

it('shows the privacy and terms pages to guests, with the temporary-text banner', function () {
    $this->get(route('legal.privacy'))->assertOk()->assertSee('Kebijakan Privasi TeleFisioRehab')->assertSee('Teks sementara')
        ->assertDontSee(config('legal.placeholder_marker'));
    $this->get(route('legal.terms'))->assertOk()->assertSee('Syarat Penggunaan TeleFisioRehab')->assertSee('112');

    expect(Legal::hasPlaceholder())->toBeTrue();
});

it('drops the banner once a text no longer carries the marker', function () {
    $original = file_get_contents(Legal::path('syarat'));
    file_put_contents(Legal::path('syarat'), "# Syarat Penggunaan\n\nTeks final.");

    try {
        $this->get(route('legal.terms'))->assertOk()->assertDontSee('Teks sementara');
        expect(Legal::isPlaceholder('syarat'))->toBeFalse()->and(Legal::hasPlaceholder())->toBeTrue();     // privacy still temporary
    } finally {
        file_put_contents(Legal::path('syarat'), $original);
    }
});

it('links the texts from the landing and register pages', function () {
    $this->get('/')->assertSee(route('legal.privacy'))->assertSee(route('legal.terms'));
    $this->get(route('register'))->assertSee('Dengan mendaftar, Anda menyetujui');
});

// ---------------------------------------------------------------- data request

it('shows the data request section with the support email', function () {
    config(['pilot.support_email' => 'dukungan@telefisiorehab.com']);
    $user = makeUser(Role::Patient, $this->clinic);
    makePatient($user);

    $this->actingAs($user)->get(route('profile'))->assertOk()
        ->assertSee('Permintaan data saya')->assertSee('dukungan@telefisiorehab.com')->assertSee('14 hari');

    config(['pilot.support_email' => null]);
    $this->get(route('profile'))->assertSee('Hubungi klinik tempat Anda terdaftar');
});

<?php

use App\Enums\Role;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use App\Support\Staff\Invitations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 09 S4.2: a clinic admin invites a therapist; the link is single use, expires and only its hash is stored.

beforeEach(function () {
    $this->withoutVite();

    $this->clinic = makeClinic('Klinik Sehat');
    $this->admin = makeUser(Role::ClinicAdmin, $this->clinic);
});

/** The token from an invitation link. */
function tokenOf(string $url): string
{
    return Str::afterLast($url, '/');
}

it('stores only a hash of the token and expires after 7 days (DRAFT)', function () {
    $result = Invitations::invite($this->admin, $this->clinic, 'Dimas@Klinik.id');
    $invitation = $result['invitation']->fresh();
    $token = tokenOf($result['url']);

    expect($invitation->email)->toBe('dimas@klinik.id')
        ->and($invitation->token_hash)->toBe(hash('sha256', $token))
        ->and($invitation->token_hash)->not->toBe($token)
        ->and(StaffInvitation::where('token_hash', $token)->exists())->toBeFalse()
        ->and(Invitations::EXPIRES_DAYS)->toBe(7)
        ->and($invitation->expires_at->toDateString())->toBe(now()->addDays(7)->toDateString());
});

it('emails the link when a real mailer is set up', function () {
    config(['mail.default' => 'smtp']);
    Notification::fake();

    $result = Invitations::invite($this->admin, $this->clinic, 'dimas@klinik.id');

    expect($result['mailed'])->toBeTrue();
    Notification::assertSentTo(new AnonymousNotifiable, StaffInvitationNotification::class,
        fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'dimas@klinik.id' && $n->url === $result['url']);
});

it('shows the link to copy on the team page when mail is not set up', function () {
    $this->actingAs($this->admin);

    Volt::test('pages.clinic.team')->set('email', 'dimas@klinik.id')->call('invite')->assertHasNoErrors()
        ->assertSee('Salin tautan ini')->assertSee(url('/invitation/'), false)->assertSee('dimas@klinik.id');
});

it('lets the invited person create a therapist account of the clinic and signs them in', function () {
    $token = tokenOf(Invitations::invite($this->admin, $this->clinic, 'dimas@klinik.id')['url']);

    $this->get(route('invitation.accept', $token))->assertOk()->assertSee('Bergabung dengan Klinik Sehat')->assertSee('dimas@klinik.id');

    Volt::test('pages.auth.accept-invitation', ['token' => $token])
        ->set('name', 'Dimas Pratama, S.Ft.')->set('password', 'rahasia123')->set('password_confirmation', 'rahasia123')
        ->call('accept')->assertHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'dimas@klinik.id')->sole();
    expect($user->role)->toBe(Role::Therapist)
        ->and($user->clinic_id)->toBe($this->clinic->id)
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(StaffInvitation::sole()->accepted_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('refuses a used, expired, cancelled or unknown link', function () {
    $used = tokenOf(Invitations::invite($this->admin, $this->clinic, 'satu@klinik.id')['url']);
    Invitations::accept($used, 'Satu', 'rahasia123');
    auth()->logout();

    $expired = tokenOf(Invitations::invite($this->admin, $this->clinic, 'dua@klinik.id')['url']);
    $this->travel(Invitations::EXPIRES_DAYS + 1)->days();

    $this->travelBack();
    $cancelled = Invitations::invite($this->admin, $this->clinic, 'tiga@klinik.id');
    Invitations::cancel($cancelled['invitation']);

    $this->travel(Invitations::EXPIRES_DAYS + 1)->days();      // "dua" is expired now
    foreach ([$used, $expired, tokenOf($cancelled['url']), 'tidak-ada'] as $token) {
        $this->get(route('invitation.accept', $token))->assertOk()->assertSee('Undangan tidak berlaku');
        expect(fn () => Invitations::accept($token, 'Orang Lain', 'rahasia123'))->toThrow(Illuminate\Validation\ValidationException::class);
    }

    expect(User::where('role', Role::Therapist)->count())->toBe(1);
});

it('refuses to invite an email that already has an account or a pending invitation', function () {
    makeUser(attributes: ['email' => 'ada@klinik.id']);
    Invitations::invite($this->admin, $this->clinic, 'baru@klinik.id');

    expect(fn () => Invitations::invite($this->admin, $this->clinic, 'ada@klinik.id'))->toThrow(Illuminate\Validation\ValidationException::class)
        ->and(fn () => Invitations::invite($this->admin, $this->clinic, 'baru@klinik.id'))->toThrow(Illuminate\Validation\ValidationException::class);
});

it('lets only the clinic own admin invite', function () {
    $other = makeUser(Role::ClinicAdmin, makeClinic('Klinik Lain'));

    expect($this->admin->can('invite', $this->clinic))->toBeTrue()
        ->and($other->can('invite', $this->clinic))->toBeFalse()
        ->and(makeUser(Role::Therapist, $this->clinic)->can('invite', $this->clinic))->toBeFalse()
        ->and(makeUser(Role::PlatformAdmin)->can('invite', $this->clinic))->toBeFalse();
});

it('cancels a pending invitation from the team page without deleting it', function () {
    $invitation = Invitations::invite($this->admin, $this->clinic, 'dimas@klinik.id')['invitation'];
    $this->actingAs($this->admin);

    Volt::test('pages.clinic.team')->assertSee('dimas@klinik.id')->call('cancelInvitation', $invitation->id)->assertDontSee('dimas@klinik.id');

    expect($invitation->fresh()->isPending())->toBeFalse()->and(StaffInvitation::count())->toBe(1);
});

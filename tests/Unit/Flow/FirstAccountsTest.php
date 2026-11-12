<?php

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 11 E3: the first accounts of a pilot without seeding (pilot:create-admin, pilot:create-clinic).

beforeEach(fn () => $this->withoutVite());

function createClinic(array $options = [])
{
    return Artisan::call('pilot:create-clinic', ['name' => 'Klinik Sehat Jakarta', '--admin-name' => 'Admin Klinik', '--admin-email' => 'admin@klinik.id'] + $options);
}

it('creates the platform admin and emails a set-password link', function () {
    Notification::fake();

    expect(Artisan::call('pilot:create-admin', ['--name' => 'Pemilik', '--email' => 'Pemilik@Contoh.id']))->toBe(0)
        ->and(Artisan::output())->toContain('Platform admin created: pemilik@contoh.id')->toContain('Set-password email sent');

    $admin = User::where('email', 'pemilik@contoh.id')->sole();
    expect($admin->role)->toBe(Role::PlatformAdmin)->and($admin->clinic_id)->toBeNull();
    Notification::assertSentTo($admin, SetPasswordNotification::class);
});

it('creates the clinic with its code and its admin', function () {
    Notification::fake();

    expect(createClinic())->toBe(0);
    $output = Artisan::output();

    $clinic = Clinic::where('name', 'Klinik Sehat Jakarta')->sole();
    $admin = User::where('email', 'admin@klinik.id')->sole();
    expect($admin->role)->toBe(Role::ClinicAdmin)->and($admin->clinic_id)->toBe($clinic->id)
        ->and($output)->toContain('Clinic code: '.$clinic->join_code)->toContain($clinic->registerUrl());
    Notification::assertSentTo($admin, SetPasswordNotification::class, fn ($n) => $n->clinicName === 'Klinik Sehat Jakarta');
});

it('runs twice without duplicates, and sends a new link only with --resend', function () {
    Notification::fake();
    createClinic();
    createClinic();

    expect(Clinic::count())->toBe(1)->and(User::count())->toBe(1)
        ->and(Artisan::output())->toContain('already exists')->toContain('--resend');
    Notification::assertSentTimes(SetPasswordNotification::class, 1);

    createClinic(['--resend' => true]);
    Notification::assertSentTimes(SetPasswordNotification::class, 2);
});

it('refuses an email that belongs to another role or clinic, changing nothing', function () {
    $patient = makeUser(attributes: ['email' => 'pasien@contoh.id']);

    expect(Artisan::call('pilot:create-admin', ['--name' => 'X', '--email' => 'pasien@contoh.id']))->toBe(1)
        ->and(Artisan::output())->toContain('sudah dipakai akun lain');
    expect($patient->fresh()->role)->toBe(Role::Patient);

    createClinic();
    expect(Artisan::call('pilot:create-clinic', ['name' => 'Klinik Lain', '--admin-name' => 'A', '--admin-email' => 'admin@klinik.id']))->toBe(1);
});

it('asks for the missing options', function () {
    expect(Artisan::call('pilot:create-admin', ['--email' => 'a@contoh.id']))->toBe(1)
        ->and(Artisan::call('pilot:create-clinic', ['name' => 'Klinik']))->toBe(1)
        ->and(User::count())->toBe(0);
});

it('never prints or logs a password or the link', function () {
    $log = tempnam(sys_get_temp_dir(), 'log');
    config(['logging.channels.uji' => ['driver' => 'single', 'path' => $log], 'logging.default' => 'uji']);

    createClinic();
    $output = Artisan::output();
    $message = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
    preg_match('#reset-password/([A-Za-z0-9]+)#', $message->getHtmlBody(), $m);

    expect($m[1] ?? null)->not->toBeNull()
        ->and($output)->not->toContain($m[1])->not->toContain('reset-password')
        ->and(file_get_contents($log))->not->toContain($m[1]);
});

it('sends the set-password email in Indonesian, without a password', function () {
    createClinic();
    $message = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();

    expect($message->getSubject())->toBe('Atur kata sandi akun '.config('app.name').' Anda')
        ->and($message->getHtmlBody())->toContain('admin klinik untuk Klinik Sehat Jakarta')->toContain('Atur kata sandi')
        ->not->toContain('password:');
});

it('says so when the email fails, and suggests --resend', function () {
    Mail::extend('failing', fn () => new class extends Symfony\Component\Mailer\Transport\AbstractTransport
    {
        protected function doSend(Symfony\Component\Mailer\SentMessage $message): void
        {
            throw new Symfony\Component\Mailer\Exception\TransportException('down');
        }

        public function __toString(): string
        {
            return 'failing://';
        }
    });
    config(['mail.mailers.failing' => ['transport' => 'failing'], 'mail.default' => 'failing']);
    Log::spy();

    expect(createClinic())->toBe(1)
        ->and(Artisan::output())->toContain('could not be sent')->toContain('--resend');
    expect(User::count())->toBe(1);                                    // the account exists, so --resend can follow
    Log::shouldHaveReceived('error')->with('mail.failed', Mockery::type('array'));
});

it('lets the new admin set a password and reach the clinic dashboard, verified', function () {
    createClinic();
    $admin = User::where('email', 'admin@klinik.id')->sole();
    expect($admin->hasVerifiedEmail())->toBeFalse();

    $message = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
    preg_match('#reset-password/([A-Za-z0-9]+)#', $message->getHtmlBody(), $m);

    Volt::test('pages.auth.reset-password', ['token' => $m[1]])
        ->set('email', 'admin@klinik.id')->set('password', 'KataSandiBaru123')->set('password_confirmation', 'KataSandiBaru123')
        ->call('resetPassword')->assertHasNoErrors();

    expect($admin->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->actingAs($admin->fresh())->get(route('dashboard'))->assertRedirect(route('clinic.dashboard'));
    $this->get(route('clinic.dashboard'))->assertOk()->assertSee('Klinik Sehat Jakarta');
});

<?php

use App\Console\Commands\MailTest;
use App\Enums\RiskTier;
use App\Enums\Role;
use App\Models\User;
use App\Support\Staff\Invitations;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Volt;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;

require_once __DIR__.'/../../Helpers.php';

uses(TestCase::class, RefreshDatabase::class);

// PRD 11 E1: real mail. Indonesian emails, no personal or clinical data, MAIL_ALWAYS_TO, mail:test, and a mail failure
// never breaks an action.

beforeEach(function () {
    $this->withoutVite();
    @unlink(MailTest::path());
});

afterEach(fn () => @unlink(MailTest::path()));

/** Every message the array mailer captured, as [to, cc, bcc, envelope recipients, headers, subject, text+html]. */
function sentMail(): array
{
    return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->map(function (SentMessage $sent) {
        $m = $sent->getOriginalMessage();

        return [
            'to' => array_map(fn ($a) => $a->getAddress(), $m->getTo()),
            'cc' => array_map(fn ($a) => $a->getAddress(), $m->getCc()),
            'bcc' => array_map(fn ($a) => $a->getAddress(), $m->getBcc()),
            'envelope' => array_map(fn ($a) => $a->getAddress(), $sent->getEnvelope()->getRecipients()),
            'original_to' => $m->getHeaders()->get('X-Original-To')?->getBodyAsString(),
            'subject' => $m->getSubject(),
            'body' => $m->getTextBody().' '.$m->getHtmlBody(),
        ];
    })->all();
}

/** A mail transport that always fails, like an SMTP server that is down. */
function failMail(): void
{
    Mail::extend('failing', fn () => new class extends AbstractTransport
    {
        protected function doSend(SentMessage $message): void
        {
            throw new TransportException('Expected response code 250 but got 550 for recipient pasien@contoh.id');
        }

        public function __toString(): string
        {
            return 'failing://';
        }
    });
    config(['mail.mailers.failing' => ['transport' => 'failing'], 'mail.default' => 'failing']);
    app('mail.manager')->purge('failing');
}

// ---------------------------------------------------------------- Indonesian

it('sends the verification email in Indonesian, with the non-diagnostic footer', function () {
    $mail = (new VerifyEmail)->toMail(makeUser());

    expect($mail->subject)->toBe('Verifikasi alamat email Anda')
        ->and($mail->actionText)->toBe('Verifikasi email');

    $html = (string) $mail->render();
    expect($html)->toContain('Klik tombol di bawah untuk memverifikasi')
        ->toContain('Salam,')
        ->toContain('bukan diagnosis')
        ->toContain('mohon tidak dibalas')
        ->not->toContain('Regards')->not->toContain('Verify');
});

it('sends the password reset email in Indonesian', function () {
    $mail = (new ResetPassword('token-123'))->toMail(makeUser());

    expect($mail->subject)->toBe('Atur ulang kata sandi')
        ->and($mail->actionText)->toBe('Atur ulang kata sandi')
        ->and((string) $mail->render())->toContain('mengatur ulang kata sandi')->toContain('Jika tombol')->not->toContain('Reset Password');
});

it('shows validation messages in Indonesian', function () {
    $errors = Volt::test('pages.auth.register')->call('register')->errors();

    expect($errors->first('name'))->toBe('Kolom nama wajib diisi.')
        ->and($errors->first('email'))->toBe('Kolom email wajib diisi.')
        ->and(app()->getLocale())->toBe('id');
});

// ---------------------------------------------------------------- no personal or clinical data

it('never puts a patient name or clinical details in an email', function () {
    $clinic = makeClinic('Klinik Sehat');
    $user = makeUser(clinic: $clinic, attributes: ['name' => 'Sri Rahasia Wulandari', 'email_verified_at' => null]);
    $this->patient = makePatient($user);
    stratified(RiskTier::High, regions: ['Left Knee'], pain: 8);
    lockPatient($this->patient, ['chest_pain']);

    $user->sendEmailVerificationNotification();
    Password::sendResetLink(['email' => $user->email]);
    config(['mail.default' => 'array']);
    Invitations::invite(makeUser(Role::ClinicAdmin, $clinic), $clinic, 'terapis.baru@contoh.id');      // logs, not sends, on array
    Artisan::call('mail:test', ['address' => 'pemilik@contoh.id']);

    $mails = sentMail();
    expect($mails)->toHaveCount(3);
    foreach ($mails as $mail) {
        expect($mail['subject'].' '.$mail['body'])
            ->not->toContain('Sri Rahasia')->not->toContain('Wulandari')
            ->not->toContain('Risiko')->not->toContain('Lutut')->not->toContain('nyeri')->not->toContain('red flag')->not->toContain('Nyeri dada');
    }
});

// ---------------------------------------------------------------- a mail failure never breaks an action

it('still registers the account when the mail server fails, and logs without personal data', function () {
    config(['pilot.registration_mode' => 'open']);      // no clinic code (PRD 11 E3 tests cover the code)
    failMail();
    Log::spy();

    Volt::test('pages.auth.register')
        ->set('name', 'Budi Gagal Kirim')->set('email', 'budi@contoh.id')
        ->set('password', 'password')->set('password_confirmation', 'password')
        ->call('register')->assertHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'budi@contoh.id')->sole();
    Log::shouldHaveReceived('error')->once()->with('mail.failed', Mockery::on(fn (array $context) => $context === [
        'user_id' => $user->id, 'exception' => TransportException::class,
    ]));
});

it('keeps forgot-password and resend verification working when the mail server fails', function () {
    $user = makeUser(attributes: ['email_verified_at' => null]);
    failMail();
    Log::spy();

    Volt::test('pages.auth.forgot-password')->set('email', $user->email)->call('sendPasswordResetLink')->assertHasNoErrors();

    $this->actingAs($user);
    Volt::test('pages.auth.verify-email')->call('sendVerification')->assertHasNoErrors();

    Log::shouldHaveReceived('error')->twice()->with('mail.failed', Mockery::on(fn (array $context) => ! str_contains(json_encode($context), '@')
        && array_keys($context) === ['user_id', 'exception']));
});

it('creates a staff invitation and shows the link when the mail server fails', function () {
    $clinic = makeClinic();
    failMail();
    Log::spy();

    $result = Invitations::invite(makeUser(Role::ClinicAdmin, $clinic), $clinic, 'terapis@contoh.id');

    expect($result['mailed'])->toBeFalse()->and($result['url'])->toContain('/invitation/');
    Log::shouldHaveReceived('error')->once()->with('mail.failed', Mockery::type('array'));
});

// ---------------------------------------------------------------- MAIL_ALWAYS_TO

it('sends every email only to MAIL_ALWAYS_TO when it is set', function () {
    config(['mail.always_to' => 'pemilik@contoh.id']);

    Mail::raw('Isi', fn ($m) => $m->to('pasien@contoh.id')->cc('dokter@contoh.id')->bcc('admin@contoh.id')->subject('Uji'));
    makeUser(attributes: ['email' => 'baru@contoh.id', 'email_verified_at' => null])->sendEmailVerificationNotification();

    $mails = sentMail();
    expect($mails)->toHaveCount(2);
    foreach ($mails as $mail) {
        expect($mail['to'])->toBe(['pemilik@contoh.id'])
            ->and($mail['cc'])->toBe([])
            ->and($mail['bcc'])->toBe([])
            ->and($mail['envelope'])->toBe(['pemilik@contoh.id']);
    }
    expect($mails[0]['original_to'])->toBe('pasien@contoh.id, dokter@contoh.id, admin@contoh.id')
        ->and($mails[1]['original_to'])->toBe('baru@contoh.id');
});

it('sends to the real recipients when MAIL_ALWAYS_TO is empty', function () {
    Mail::raw('Isi', fn ($m) => $m->to('pasien@contoh.id')->subject('Uji'));

    expect(sentMail()[0]['to'])->toBe(['pasien@contoh.id'])->and(sentMail()[0]['original_to'])->toBeNull();
});

// ---------------------------------------------------------------- mail:test

it('records the time of a successful mail:test', function () {
    expect(Artisan::call('mail:test', ['address' => 'pemilik@contoh.id']))->toBe(0)
        ->and(file_exists(MailTest::path()))->toBeTrue()
        ->and(sentMail()[0]['subject'])->toBe('Email uji '.config('app.name'));
});

it('fails mail:test without recording a success, and never prints the password', function () {
    config(['mail.mailers.smtp.password' => 'RahasiaSekali123']);
    failMail();

    expect(Artisan::call('mail:test', ['address' => 'pemilik@contoh.id']))->toBe(1)
        ->and(file_exists(MailTest::path()))->toBeFalse()
        ->and(Artisan::output())->toContain('Sending failed')->not->toContain('RahasiaSekali123');
});

it('warns when MAIL_ALWAYS_TO redirects the test message', function () {
    config(['mail.always_to' => 'pemilik@contoh.id']);

    Artisan::call('mail:test', ['address' => 'lain@contoh.id']);

    expect(Artisan::output())->toContain('MAIL_ALWAYS_TO is set');
});

it('refuses something that is not an email address', function () {
    expect(Artisan::call('mail:test', ['address' => 'bukan-email']))->toBe(1);
});

// ---------------------------------------------------------------- synchronous

it('keeps mail synchronous: nothing in the app is queued', function () {
    foreach (Finder::create()->files()->in(app_path())->name('*.php') as $file) {
        expect(str_contains($file->getContents(), 'ShouldQueue'))->toBeFalse($file->getRelativePathname().' queues work, but there is no queue worker');
    }

    expect(interface_exists(ShouldQueue::class))->toBeTrue();
});

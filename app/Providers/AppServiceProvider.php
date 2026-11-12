<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Address;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->redirectMailOnStaging();
        $this->indonesianAuthMail();
    }

    /**
     * MAIL_ALWAYS_TO (PRD 11 E1): on staging every outgoing message goes to one address only, so a staging copy can never
     * email real people. Runs on the message itself, so it covers notifications, mailables and every mailer.
     */
    private function redirectMailOnStaging(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $to = config('mail.always_to');
            if (! $to) {
                return;
            }

            $message = $event->message;
            $original = array_map(fn (Address $a) => $a->getAddress(), [...$message->getTo(), ...$message->getCc(), ...$message->getBcc()]);

            $message->to($to);
            $message->getHeaders()->remove('Cc');
            $message->getHeaders()->remove('Bcc');
            $message->getHeaders()->addTextHeader('X-Original-To', implode(', ', $original));
        });
    }

    /** The verification and password reset emails in Indonesian. No name or clinical detail: only the link (PRD 11 E1). */
    private function indonesianAuthMail(): void
    {
        VerifyEmail::toMailUsing(fn ($notifiable, string $url) => (new MailMessage)
            ->subject('Verifikasi alamat email Anda')
            ->greeting('Halo,')
            ->line('Terima kasih telah mendaftar di '.config('app.name').'. Klik tombol di bawah untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi email', $url)
            ->line('Tautan ini berlaku '.config('auth.verification.expire', 60).' menit.')
            ->line('Jika Anda tidak membuat akun, abaikan email ini.'));

        ResetPassword::toMailUsing(fn ($notifiable, string $token) => (new MailMessage)
            ->subject('Atur ulang kata sandi')
            ->greeting('Halo,')
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda.')
            ->action('Atur ulang kata sandi', url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false)))
            ->line('Tautan ini berlaku '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60).' menit.')
            ->line('Jika Anda tidak meminta pengaturan ulang kata sandi, abaikan email ini.'));
    }
}

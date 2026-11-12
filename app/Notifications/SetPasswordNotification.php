<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The first email of an account made by `pilot:create-admin` or `pilot:create-clinic` (PRD 11 E3): a link to choose a
 * password. Nobody else ever knows the password. Setting it also confirms the email address.
 */
class SetPasswordNotification extends Notification
{
    public function __construct(
        #[\SensitiveParameter] public string $token,
        public string $role,
        public ?string $clinicName = null,
    ) {
    }

    /** @return string[] */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Atur kata sandi akun '.config('app.name').' Anda')
            ->greeting('Halo,')
            ->line('Akun '.$this->role.($this->clinicName ? ' untuk '.$this->clinicName : '').' sudah dibuat untuk alamat email ini di '.config('app.name').'.')
            ->line('Klik tombol di bawah untuk memilih kata sandi Anda. Setelah itu Anda bisa langsung masuk.')
            ->action('Atur kata sandi', url(route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false)))
            ->line("Tautan ini berlaku $minutes menit. Jika sudah kedaluwarsa, minta tautan baru kepada pengelola aplikasi.")
            ->line('Jika Anda tidak mengenal akun ini, abaikan email ini.');
    }
}

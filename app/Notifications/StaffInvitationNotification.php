<?php

namespace App\Notifications;

use DateTimeInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The invitation email to a new therapist (PRD 09 S4.2). */
class StaffInvitationNotification extends Notification
{
    public function __construct(
        public string $clinicName,
        public string $url,
        public DateTimeInterface $expiresAt,
    ) {
    }

    /** @return string[] */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Undangan bergabung dengan '.$this->clinicName)
            ->greeting('Halo,')
            ->line("Anda diundang bergabung sebagai fisioterapis di {$this->clinicName} pada ".config('app.name').'.')
            ->action('Terima undangan', $this->url)
            ->line('Tautan berlaku sampai '.$this->expiresAt->format('d-m-Y H:i').' dan hanya bisa dipakai sekali.')
            ->line('Abaikan email ini jika Anda tidak mengenal klinik tersebut.');
    }
}

<?php

namespace App\Support\Staff;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Inviting a therapist to a clinic (PRD 09 S4.2). The link carries a random token; only its SHA-256 hash is
 * stored. An invitation is single use and expires after EXPIRES_DAYS. Callers authorise first (ClinicPolicy).
 */
class Invitations
{
    public const EXPIRES_DAYS = 7;          // DRAFT: clinic to confirm (D5)

    /**
     * Creates the invitation and emails the link.
     *
     * @return array{invitation: StaffInvitation, url: string, mailed: bool} the link is for copying when mail did not go out
     */
    public static function invite(User $by, Clinic $clinic, string $email): array
    {
        $email = Str::lower(trim($email));

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Email ini sudah terdaftar sebagai akun.']);
        }

        if (StaffInvitation::query()->where('email', $email)->where('clinic_id', $clinic->id)
            ->whereNull('accepted_at')->where('expires_at', '>', now())->exists()) {
            throw ValidationException::withMessages(['email' => 'Undangan untuk email ini masih berlaku.']);
        }

        $token = Str::random(48);
        $invitation = StaffInvitation::query()->create([
            'clinic_id' => $clinic->id,
            'email' => $email,
            'role' => Role::Therapist,
            'token_hash' => self::hash($token),
            'expires_at' => now()->addDays(self::EXPIRES_DAYS),
            'invited_by' => $by->id,
        ]);

        $url = route('invitation.accept', $token);

        return ['invitation' => $invitation, 'url' => $url, 'mailed' => self::mail($invitation, $clinic, $url)];
    }

    /** A pending invitation for this token, or null (unknown, used, expired or cancelled). */
    public static function find(string $token): ?StaffInvitation
    {
        $invitation = StaffInvitation::query()->where('token_hash', self::hash($token))->first();

        return $invitation?->isPending() ? $invitation : null;
    }

    /** Creates the staff account. Single use: a second accept of the same token fails. */
    public static function accept(string $token, string $name, string $password): User
    {
        return DB::transaction(function () use ($token, $name, $password) {
            $invitation = StaffInvitation::query()->where('token_hash', self::hash($token))->lockForUpdate()->first();

            if (! $invitation?->isPending()) {
                throw ValidationException::withMessages(['token' => 'Undangan tidak berlaku lagi.']);
            }

            if (User::query()->where('email', $invitation->email)->exists()) {
                throw ValidationException::withMessages(['token' => 'Email ini sudah terdaftar sebagai akun.']);
            }

            $user = new User(['name' => trim($name), 'email' => $invitation->email, 'password' => $password]);
            $user->forceFill([
                'role' => $invitation->role,
                'clinic_id' => $invitation->clinic_id,
                'email_verified_at' => now(),           // the link reached this inbox
            ])->save();

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });
    }

    /** Cancelling only ends the validity; the row stays. */
    public static function cancel(StaffInvitation $invitation): void
    {
        $invitation->update(['expires_at' => now()]);
    }

    /** True when the email went out through a real mailer. */
    private static function mail(StaffInvitation $invitation, Clinic $clinic, string $url): bool
    {
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            return false;
        }

        try {
            Notification::route('mail', $invitation->email)->notifyNow(new StaffInvitationNotification($clinic->name, $url, $invitation->expires_at));

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}

<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\Clinic;
use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The first accounts of a pilot, made without seeding (PRD 11 E3): the platform admin, and each clinic with its admin.
 * Idempotent. Accounts get a random password nobody knows and a set-password email instead.
 */
class FirstAccounts
{
    public const ROLE_LABELS = [
        Role::PlatformAdmin->value => 'admin platform',
        Role::ClinicAdmin->value => 'admin klinik',
    ];

    /**
     * Finds or creates the account. Refuses an email that belongs to another role or another clinic.
     *
     * @return array{user: User, created: bool}
     */
    public static function account(string $name, string $email, Role $role, ?Clinic $clinic = null): array
    {
        $email = Str::lower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("'$email' bukan alamat email yang valid.");
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            if ($user->role !== $role || (int) $user->clinic_id !== (int) $clinic?->id) {
                throw new InvalidArgumentException("Email $email sudah dipakai akun lain (peran atau klinik berbeda). Tidak ada yang diubah.");
            }

            return ['user' => $user, 'created' => false];
        }

        $user = new User(['name' => trim($name), 'email' => $email, 'password' => Str::password(40)]);
        $user->forceFill(['role' => $role, 'clinic_id' => $clinic?->id])->save();

        return ['user' => $user, 'created' => true];
    }

    /** Finds a clinic by name or creates it (with its join code). @return array{clinic: Clinic, created: bool} */
    public static function clinic(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Nama klinik wajib diisi.');
        }

        $clinic = Clinic::query()->where('name', $name)->first();
        if ($clinic) {
            return ['clinic' => $clinic, 'created' => false];
        }

        $slug = Str::slug($name);
        if (Clinic::query()->where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(4));
        }

        return ['clinic' => Clinic::query()->create(['name' => $name, 'slug' => $slug, 'status' => 'active']), 'created' => true];
    }

    /** Emails a fresh set-password link. False when the mail failed (logged as mail.failed). */
    public static function sendSetPasswordLink(User $user, ?Clinic $clinic = null): bool
    {
        $token = Password::broker()->createToken($user);
        $role = self::ROLE_LABELS[$user->role->value] ?? $user->role->value;

        return SafeMail::send(fn () => $user->notify(new SetPasswordNotification($token, $role, $clinic?->name)), $user->id);
    }
}

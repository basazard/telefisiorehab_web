<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\User;
use App\Support\FirstAccounts;
use Illuminate\Console\Command;

/** Shared tail of the pilot:create-* commands: send (or not) the set-password link and report it. */
final class SetPasswordLink
{
    public static function send(Command $command, User $user, ?Clinic $clinic, bool $send): int
    {
        if (! $send) {
            $command->line('No email sent. To send a new set-password link, run the same command with --resend.');

            return Command::SUCCESS;
        }

        if (! FirstAccounts::sendSetPasswordLink($user, $clinic)) {
            $command->error('The set-password email could not be sent (see "mail.failed" in the log). '
                .'Fix the mail settings (php artisan mail:test), then run the same command with --resend.');

            return Command::FAILURE;
        }

        if ($redirect = config('mail.always_to')) {
            $command->warn("MAIL_ALWAYS_TO is set: the set-password email went to $redirect.");
        }
        $command->info('Set-password email sent to '.$user->email.'. The link is valid for '
            .config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60).' minutes.');

        return Command::SUCCESS;
    }
}

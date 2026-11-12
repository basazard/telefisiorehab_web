<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Support\FirstAccounts;
use Illuminate\Console\Command;
use InvalidArgumentException;

/** PRD 11 E3: the platform admin of a pilot, without seeding. Never prints or logs a password or a link. */
class PilotCreateAdmin extends Command
{
    protected $signature = 'pilot:create-admin
        {--name= : Full name}
        {--email= : Email address (receives the set-password link)}
        {--resend : Send a new set-password link to an existing admin}';

    protected $description = 'Create the platform admin and email a set-password link (idempotent)';

    public function handle(): int
    {
        if (! $this->option('email') || ! $this->option('name')) {
            $this->error('Usage: php artisan pilot:create-admin --name="Full Name" --email=you@example.com');

            return self::FAILURE;
        }

        try {
            ['user' => $user, 'created' => $created] = FirstAccounts::account($this->option('name'), $this->option('email'), Role::PlatformAdmin);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(($created ? 'Platform admin created: ' : 'Platform admin already exists: ').$user->email);

        return SetPasswordLink::send($this, $user, null, $created || $this->option('resend'));
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Support\FirstAccounts;
use Illuminate\Console\Command;
use InvalidArgumentException;

/** PRD 11 E3: a clinic with its clinic admin, without seeding. Never prints or logs a password or a link. */
class PilotCreateClinic extends Command
{
    protected $signature = 'pilot:create-clinic
        {name : Clinic name, e.g. "Klinik Sehat Jakarta"}
        {--admin-name= : Full name of the clinic admin}
        {--admin-email= : Email of the clinic admin (receives the set-password link)}
        {--resend : Send a new set-password link to an existing clinic admin}';

    protected $description = 'Create a clinic (with its join code) and its clinic admin, and email a set-password link (idempotent)';

    public function handle(): int
    {
        if (! $this->option('admin-email') || ! $this->option('admin-name')) {
            $this->error('Usage: php artisan pilot:create-clinic "Nama Klinik" --admin-name="Full Name" --admin-email=admin@example.com');

            return self::FAILURE;
        }

        try {
            ['clinic' => $clinic, 'created' => $clinicCreated] = FirstAccounts::clinic($this->argument('name'));
            ['user' => $admin, 'created' => $created] = FirstAccounts::account(
                $this->option('admin-name'), $this->option('admin-email'), Role::ClinicAdmin, $clinic);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(($clinicCreated ? 'Clinic created: ' : 'Clinic already exists: ').$clinic->name);
        $this->line('Clinic code: '.$clinic->join_code);
        $this->line('Patient register link: '.$clinic->registerUrl());
        $this->info(($created ? 'Clinic admin created: ' : 'Clinic admin already exists: ').$admin->email);

        return SetPasswordLink::send($this, $admin, $clinic, $created || $this->option('resend'));
    }
}

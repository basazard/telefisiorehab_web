<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Demo data for the staff side (PRD 09 S1): two clinics, their staff and patients in every state of the patient
 * journey. Run with `php artisan db:seed --class=DemoSeeder`. Safe to run again: accounts are matched by email and
 * a patient's history is only created once. Every demo account uses the password "password".
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        self::guard();

        $this->call([ExerciseSeeder::class, ClinicSeeder::class, PatientSeeder::class, ProgramSeeder::class]);
    }

    /** Environments where demo data is allowed (PRD 11 E2); testing runs the seeder in the test suite. */
    public const ENVIRONMENTS = ['local', 'staging', 'testing'];

    /** Every demo seeder calls this first: demo data never reaches the pilot database, also via db:seed --class. */
    public static function guard(): void
    {
        if (! app()->environment(self::ENVIRONMENTS)) {
            throw new RuntimeException('Data demo hanya boleh di local atau staging, bukan di APP_ENV='.app()->environment().'.');
        }
    }

    /** Creates or updates a verified demo account. The password is only set when the account is new. */
    public static function user(string $email, string $name, Role $role, ?int $clinicId): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'role' => $role,
            'clinic_id' => $clinicId,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        if (! $user->exists) {
            $user->password = self::PASSWORD;
        }

        $user->save();

        return $user;
    }
}

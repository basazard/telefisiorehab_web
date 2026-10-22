<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Clinic;
use Illuminate\Database\Seeder;

/** Demo clinics and their staff (part of DemoSeeder). */
class ClinicSeeder extends Seeder
{
    /** key => [name, slug] */
    public const CLINICS = [
        'a' => ['Klinik Sehat Jakarta', 'klinik-sehat-jakarta'],
        'b' => ['Klinik Bugar Bandung', 'klinik-bugar-bandung'],
    ];

    /** [email, name, role, clinic key] */
    public const STAFF = [
        ['admin@demo.test', 'Admin Platform', Role::PlatformAdmin, null],
        ['admin.a@demo.test', 'Admin Klinik Sehat', Role::ClinicAdmin, 'a'],
        ['ani.a@demo.test', 'Ani Lestari, S.Ft.', Role::Therapist, 'a'],
        ['budi.a@demo.test', 'Budi Hartono, S.Ft.', Role::Therapist, 'a'],
        ['admin.b@demo.test', 'Admin Klinik Bugar', Role::ClinicAdmin, 'b'],
        ['citra.b@demo.test', 'Citra Wulandari, S.Ft.', Role::Therapist, 'b'],
    ];

    public function run(): void
    {
        foreach (self::CLINICS as [$name, $slug]) {
            Clinic::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'status' => 'active']);
        }

        foreach (self::STAFF as [$email, $name, $role, $clinic]) {
            DemoSeeder::user($email, $name, $role, $clinic ? self::clinic($clinic)->id : null);
        }
    }

    public static function clinic(string $key): Clinic
    {
        return Clinic::query()->where('slug', self::CLINICS[$key][1])->firstOrFail();
    }
}

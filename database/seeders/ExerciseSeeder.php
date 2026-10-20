<?php

namespace Database\Seeders;

use App\Support\Onboarding\ExerciseLibrary;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    /**
     * The built-in exercise library (global, clinic_id null). Safe to run again: it upserts by slug.
     */
    public function run(): void
    {
        ExerciseLibrary::sync();
    }
}

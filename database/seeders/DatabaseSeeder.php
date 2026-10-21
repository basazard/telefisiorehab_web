<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. Accounts are created through /register (new accounts are patients).
     */
    public function run(): void
    {
        $this->call(ExerciseSeeder::class);
    }
}

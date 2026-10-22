<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A patient with a complete profile (adult). Use incomplete() for a new account, child() / elderly() for the other modules.
 *
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        $height = fake()->numberBetween(150, 180);
        $weight = fake()->numberBetween(48, 85);

        return [
            'user_id' => User::factory(),
            'clinic_id' => fn (array $attributes) => User::find($attributes['user_id'])?->clinic_id,
            'date_of_birth' => now()->subYears(fake()->numberBetween(25, 55))->subDays(fake()->numberBetween(0, 364))->toDateString(),
            'gender' => fake()->randomElement(['male', 'female']),
            'height_cm' => $height,
            'weight_kg' => $weight,
            'bmi' => round($weight / (($height / 100) ** 2), 1),
            'occupation' => fake()->randomElement(['Pekerja kantor', 'Guru', 'Pedagang', 'Ibu rumah tangga', 'Pengemudi']),
            'activity_hours_per_day' => fake()->numberBetween(2, 9),
            'has_special_condition' => false,
            'special_conditions' => [],
            'goals' => ['reduce_pain'],
            'profile_completed_at' => now(),
        ];
    }

    /** A new account that has not finished the profile wizard. */
    public function incomplete(): static
    {
        return $this->state(fn () => [
            'date_of_birth' => null, 'gender' => null, 'height_cm' => null, 'weight_kg' => null, 'bmi' => null,
            'occupation' => null, 'activity_hours_per_day' => null, 'goals' => null, 'profile_completed_at' => null,
        ]);
    }

    public function child(int $years = 8): static
    {
        return $this->state(fn () => ['date_of_birth' => now()->subYears($years)->subMonths(3)->toDateString(), 'occupation' => 'Pelajar']);
    }

    public function elderly(int $years = 72): static
    {
        return $this->state(fn () => ['date_of_birth' => now()->subYears($years)->subMonths(2)->toDateString(), 'occupation' => 'Pensiunan']);
    }
}

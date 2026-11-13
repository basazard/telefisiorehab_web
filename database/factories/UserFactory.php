<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\Legal;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Every factory user has accepted the current consent (PRD 11 E4), so tests reach the app pages. Use
     * withoutConsent() to test the consent gate.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (User $user) => Legal::accept($user));
    }

    /** A user who has not consented yet (removes the row configure() made; tests only). */
    public function withoutConsent(): static
    {
        return $this->afterCreating(fn (User $user) => DB::table('consents')->where('user_id', $user->id)->delete());
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

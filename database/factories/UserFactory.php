<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Patient;
use App\Models\User;
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
            'rol_id' => Role::Doctor->value,
        ];
    }

    public function admin(): static
    {
        return $this->state(['rol_id' => Role::Admin->value]);
    }

    public function chiefDoctor(): static
    {
        return $this->state(['rol_id' => Role::ChiefDoctor->value]);
    }

    /**
     * Portal account of a patient (US-6.3).
     */
    public function patient(?Patient $patient = null): static
    {
        return $this->state(fn () => [
            'rol_id' => Role::Patient->value,
            'patient_id' => $patient?->id ?? Patient::factory(),
        ]);
    }

    public function withoutRole(): static
    {
        return $this->state(['rol_id' => null]);
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

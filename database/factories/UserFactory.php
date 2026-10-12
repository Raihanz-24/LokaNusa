<?php

namespace Database\Factories;

use App\Enums\UserRole;
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
            'role' => UserRole::Wisatawan,
        ];
    }

    /**
     * State untuk peran tertentu.
     */
    public function role(UserRole $role): static
    {
        return $this->state(fn (array $attributes): array => ['role' => $role]);
    }

    public function superAdmin(): static
    {
        return $this->role(UserRole::SuperAdmin);
    }

    public function adminPemerintah(): static
    {
        return $this->role(UserRole::AdminPemerintah);
    }

    public function adminWilayah(): static
    {
        return $this->role(UserRole::AdminWilayah);
    }

    public function pengelolaDestinasi(): static
    {
        return $this->role(UserRole::PengelolaDestinasi);
    }

    public function pemilikUmkm(): static
    {
        return $this->role(UserRole::PemilikUmkm);
    }

    public function pengelolaPenginapan(): static
    {
        return $this->role(UserRole::PengelolaPenginapan);
    }

    public function kontributorBudaya(): static
    {
        return $this->role(UserRole::KontributorBudaya);
    }

    public function pengelolaEvent(): static
    {
        return $this->role(UserRole::PengelolaEvent);
    }

    public function wisatawan(): static
    {
        return $this->role(UserRole::Wisatawan);
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

<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'company_id' => $this->defaultCompanyId(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
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

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'company_id' => $this->defaultCompanyId(),
        ]);
    }

    public function sales(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'sales',
            'company_id' => $this->defaultCompanyId(),
        ]);
    }

    public function warehouse(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'warehouse',
            'company_id' => $this->defaultCompanyId(),
        ]);
    }

    public function finance(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'finance',
            'company_id' => $this->defaultCompanyId(),
        ]);
    }

    protected function defaultCompanyId(): int
    {
        $companyId = Company::query()->orderBy('id')->value('id');

        if ($companyId !== null) {
            return (int) $companyId;
        }

        return (int) Company::query()->firstOrCreate(
            ['slug' => 'default-company'],
            ['name' => 'Default Company', 'is_active' => true]
        )->id;
    }
}

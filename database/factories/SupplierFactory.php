<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Adria Office Supply',
            'Balkan Tech Distribution',
            'Central Workspace Systems',
            'DataCore Solutions',
            'Euro Device Hub',
            'Nordic Business Equipment',
            'Prime Components Group',
            'Vertex Procurement',
        ]);

        return [
            'name' => $name . ' ' . fake()->randomElement(['d.o.o.', 'Ltd.', 'Group', 'Distribution']) . ' ' . fake()->numerify('##'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'lead_time_days' => fake()->numberBetween(3, 21)
        ];
    }
}

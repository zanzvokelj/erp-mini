<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            'retail',
            'retail',
            'retail',
            'wholesale',
        ]);

        return [
            'name' => fake()->company(),
            'type' => $type,
            'discount_percent' => $type === 'wholesale'
                ? fake()->randomFloat(2, 3, 12)
                : fake()->randomFloat(2, 0, 5),
            'credit_limit' => $type === 'wholesale'
                ? fake()->numberBetween(5000, 35000)
                : fake()->numberBetween(250, 8000),
        ];
    }
}

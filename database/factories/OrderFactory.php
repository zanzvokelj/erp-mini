<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;
use App\Models\Warehouse;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('2026-01-01', 'now');
        $status = fake()->randomElement([
            'draft',
            'confirmed',
            'shipped',
            'completed',
            'cancelled',
        ]);

        return [

            'order_number' => 'ORD-' . fake()->unique()->numberBetween(10000,99999),

            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::query()->inRandomOrder()->value('id')
                ?? Warehouse::factory(),

            'status' => $status,

            'subtotal' => 0,
            'discount_total' => 0,
            'total' => 0,

            'confirmed_at' => in_array($status, ['confirmed', 'shipped', 'completed'])
                ? $date
                : null,

            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}

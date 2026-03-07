<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::inRandomOrder()->first();

        return [
            'product_id' => $product->id,
            'quantity' => fake()->numberBetween(1,5),
            'price_at_time' => $product->price,
            'cost_at_time' => $product->cost_price
        ];
    }
}

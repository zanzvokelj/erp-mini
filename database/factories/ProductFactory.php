<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Supplier;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::inRandomOrder()->first()->id,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-#####')),
            'name' => fake()->randomElement([
                'Desk Lamp',
                'Office Chair',
                'Laptop Stand',
                'Mechanical Keyboard',
                'Wireless Mouse',
                '4K Monitor',
                'Standing Desk',
                'USB-C Dock',
                'HD Webcam',
                'Desk Organizer',
                'Noise Cancelling Headphones',
                'Portable SSD 1TB',
                'External Hard Drive 2TB',
                'Wireless Charger',
                'Monitor Arm',
                'Ergonomic Foot Rest',
                'Desk Cable Organizer',
                'Bluetooth Speaker',
                'USB Hub',
                'Laptop Cooling Pad'
            ]),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2,10,500),
            'cost_price' => fake()->randomFloat(2,5,300),
            'min_stock' => fake()->numberBetween(5,50),
            'is_active' => true
        ];
    }
}

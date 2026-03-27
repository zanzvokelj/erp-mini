<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Supplier;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $catalog = fake()->randomElement([
            [
                'prefix' => 'MON',
                'brands' => ['Dell', 'LG', 'Samsung', 'AOC'],
                'names' => ['24" IPS Monitor', '27" QHD Monitor', '34" Ultrawide Monitor'],
                'cost_range' => [95, 420],
                'min_stock' => [4, 18],
            ],
            [
                'prefix' => 'LAP',
                'brands' => ['Lenovo', 'HP', 'Dell', 'Acer'],
                'names' => ['Business Laptop 14"', 'Ultrabook 13"', 'Performance Laptop 15"'],
                'cost_range' => [420, 1250],
                'min_stock' => [2, 10],
            ],
            [
                'prefix' => 'ACC',
                'brands' => ['Logitech', 'Anker', 'Belkin', 'Baseus'],
                'names' => ['Wireless Mouse', 'Mechanical Keyboard', 'USB-C Dock', 'Wireless Charger', 'USB Hub'],
                'cost_range' => [12, 130],
                'min_stock' => [10, 40],
            ],
            [
                'prefix' => 'ERG',
                'brands' => ['ErgoPro', 'FlexiWork', 'Workline'],
                'names' => ['Office Chair', 'Standing Desk', 'Monitor Arm', 'Foot Rest'],
                'cost_range' => [35, 390],
                'min_stock' => [3, 16],
            ],
            [
                'prefix' => 'STO',
                'brands' => ['Samsung', 'Kingston', 'SanDisk', 'WD'],
                'names' => ['Portable SSD 1TB', 'External SSD 2TB', 'USB Backup Drive 4TB'],
                'cost_range' => [55, 260],
                'min_stock' => [4, 20],
            ],
            [
                'prefix' => 'NET',
                'brands' => ['TP-Link', 'Ubiquiti', 'Netgear'],
                'names' => ['Wi-Fi 6 Router', '24-Port Switch', 'Access Point'],
                'cost_range' => [45, 320],
                'min_stock' => [3, 14],
            ],
        ]);

        $cost = fake()->randomFloat(2, $catalog['cost_range'][0], $catalog['cost_range'][1]);
        $markup = fake()->randomFloat(2, 1.22, 1.58);
        $price = round($cost * $markup, 2);
        $brand = fake()->randomElement($catalog['brands']);
        $name = $brand . ' ' . fake()->randomElement($catalog['names']);

        return [
            'supplier_id' => Supplier::query()->inRandomOrder()->value('id') ?? Supplier::factory(),
            'sku' => strtoupper($catalog['prefix'] . '-' . fake()->unique()->numerify('####')),
            'name' => $name,
            'description' => fake()->sentence(10),
            'price' => $price,
            'cost_price' => $cost,
            'min_stock' => fake()->numberBetween($catalog['min_stock'][0], $catalog['min_stock'][1]),
            'is_active' => true
        ];
    }
}

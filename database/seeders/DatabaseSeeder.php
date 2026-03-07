<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Services\OrderService;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ]);

        Supplier::factory(20)->create();

        Customer::factory(200)->create();

        Product::factory(1000)->create();

        /**
         * 1️⃣ Initial warehouse stock
         */
        Product::all()->each(function ($product) {

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => rand(100,300),
                'reference_type' => 'restock',
                'reference_id' => null
            ]);

        });

        $orderService = app(OrderService::class);

        /**
         * 2️⃣ Orders
         */
        Order::factory(5000)->create()->each(function ($order) use ($orderService) {

            $items = OrderItem::factory(rand(1,5))->create([
                'order_id' => $order->id
            ]);

            /**
             * 3️⃣ Stock OUT for each order item
             */
            foreach ($items as $item) {

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'reference_type' => 'order',
                    'reference_id' => $order->id
                ]);

            }

            $orderService->calculateTotals($order);

        });

    }
}

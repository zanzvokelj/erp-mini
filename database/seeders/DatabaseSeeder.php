<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Hash;
use App\Services\OrderService;

use Illuminate\Support\Str;


class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        /**
         * 1️⃣ Admin user
         */
        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );


        User::updateOrCreate(
            ['email' => 'sadmin@sadmin.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );


        /**
         * 2️⃣ Warehouses
         */
        $this->call(AccountingSeeder::class);
        $this->call(WarehouseSeeder::class);


        /**
         * 3️⃣ Core data
         */
        Supplier::factory(15)->create();
        Customer::factory(200)->create();
        Product::factory(1000)->create();


        /**
         * 4️⃣ Initial stock per warehouse
         */
        $warehouses = Warehouse::all();

        Product::all()->each(function ($product) use ($warehouses) {

            foreach ($warehouses as $warehouse) {

                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'type' => 'in',
                    'quantity' => rand(50,150),
                    'reference_type' => 'restock',
                    'reference_id' => null
                ]);

            }

        });


        $orderService = app(OrderService::class);


        /**
         * 5️⃣ Orders + OrderItems
         */
        Order::factory(5000)->create()->each(function ($order) use ($orderService) {

            $items = OrderItem::factory(rand(1,5))->create([
                'order_id' => $order->id
            ]);

            /**
             * Stock OUT movements
             */
            foreach ($items as $item) {

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'warehouse_id' => $order->warehouse_id,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'reference_type' => 'order',
                    'reference_id' => $order->id
                ]);

            }

            $orderService->calculateTotals($order);

        });


        /**
         * 6️⃣ Invoices + Payments
         */
        Order::with('items')
            ->where('status', 'shipped')
            ->has('items')
            ->inRandomOrder()
            ->take(1000)
            ->get()
            ->each(function ($order) {

                $invoice = Invoice::create([
                    'invoice_number' => 'INV-' . Str::random(12),
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'status' => 'draft',
                    'subtotal' => $order->subtotal,
                    'tax' => 0,
                    'total' => $order->total,
                    'issued_at' => now()
                ]);

                /**
                 * Invoice items
                 */
                foreach ($order->items as $item) {

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price_at_time,
                        'subtotal' => $item->price_at_time * $item->quantity
                    ]);

                }


                /**
                 * Random payments
                 */
                $chance = rand(1,100);

                if ($chance < 40) {
                    return;
                }

                if ($chance < 70) {

                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $invoice->total * 0.4,
                        'payment_method' => 'bank_transfer',
                        'paid_at' => now()
                    ]);

                    $invoice->update([
                        'status' => 'partial'
                    ]);

                } else {

                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $invoice->total,
                        'payment_method' => 'card',
                        'paid_at' => now()
                    ]);

                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now()
                    ]);

                }

            });

    }
}

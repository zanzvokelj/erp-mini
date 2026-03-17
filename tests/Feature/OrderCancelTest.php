<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_cancel_releases_reservation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft'
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'price_at_time' => 10,
            'cost_at_time' => 5
        ]);

        app(ProductService::class)->adjustStock(
            $product,
            $warehouse->id,
            'in',
            100,
            'restock'
        );

        app(InventoryService::class)->reserveStock($product, $order->id, 10, $warehouse->id);

        app(OrderService::class)->confirmOrder($order);

        $availableAfterConfirm = app(InventoryService::class)
            ->availableStock($product);

        $this->assertEquals(90, $availableAfterConfirm);

        app(OrderService::class)->cancelOrder($order);

        $availableAfterCancel = app(InventoryService::class)
            ->availableStock($product);

        $this->assertEquals(100, $availableAfterCancel);

        // stock se NIKOLI ni spremenil
        $stock = app(ProductService::class)
            ->calculateCurrentStock($product);

        $this->assertEquals(100, $stock);
    }
}

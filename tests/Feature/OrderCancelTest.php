<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\User;
use App\Services\OrderService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderCancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_cancel_restores_stock()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $customer = Customer::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
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
            1,
            'in',
            100,
            'restock'
        );

        app(OrderService::class)->confirmOrder($order);

        $stockAfterConfirm = app(ProductService::class)
            ->calculateCurrentStock($product);

        $this->assertEquals(90, $stockAfterConfirm);

        app(OrderService::class)->cancelOrder($order);

        $stockAfterCancel = app(ProductService::class)
            ->calculateCurrentStock($product);

        $this->assertEquals(100, $stockAfterCancel);
    }
}

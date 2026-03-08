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

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_confirmation_reduces_stock()
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
            'quantity' => 5,
            'price_at_time' => 10,
            'cost_at_time' => 5
        ]);

        app(ProductService::class)->adjustStock(
            $product,
            'in',
            100,
            'restock',
            null
        );

        app(OrderService::class)->confirmOrder($order);

        $stock = app(ProductService::class)
            ->calculateCurrentStock($product);

        $this->assertEquals(95, $stock);
    }

    public function test_order_cannot_exceed_stock()
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
            'in',
            5,
            'restock',
            null
        );

        $this->expectException(\Exception::class);

        app(OrderService::class)->confirmOrder($order);
    }
}

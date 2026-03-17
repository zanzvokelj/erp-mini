<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_cannot_be_confirmed_without_items()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft'
        ]);

        $this->expectException(\Exception::class);

        app(OrderService::class)->confirmOrder($order);
    }

    public function test_order_cannot_be_confirmed_twice()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'confirmed'
        ]);

        $this->expectException(\Exception::class);

        app(OrderService::class)->confirmOrder($order);
    }

    public function test_shipped_order_cannot_be_cancelled()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'shipped'
        ]);

        $this->expectException(\Exception::class);

        app(OrderService::class)->cancelOrder($order);
    }
}

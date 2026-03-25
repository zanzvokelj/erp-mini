<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ProductService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_api_can_create_order()
    {
        $this->actingAsAdmin(); // ✅

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $response = $this->postJson('/api/v1/orders', [
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id
        ]);
    }

    public function test_order_api_ship_uses_domain_service_flow()
    {
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        app(ProductService::class)->adjustStock(
            $product,
            $warehouse->id,
            'in',
            10,
            'restock'
        );

        $orderService = app(OrderService::class);

        $order = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($order, $product, 4);
        $orderService->confirmOrder($order);

        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 4,
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/ship");

        $response->assertOk()
            ->assertJsonPath('message', 'Order shipped successfully');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'shipped',
        ]);

        $this->assertDatabaseMissing('stock_reservations', [
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $stock = app(ProductService::class)->calculateCurrentStock($product);

        $this->assertEquals(6, $stock);
    }
}

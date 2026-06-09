<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ProductService;
use App\Services\OrderService;
use Database\Seeders\AccountingSeeder;
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
        $this->seed(AccountingSeeder::class);

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

    public function test_order_api_updates_item_through_domain_service()
    {
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'price' => 20,
            'cost_price' => 8,
        ]);

        app(ProductService::class)->adjustStock(
            $product,
            $warehouse->id,
            'in',
            10,
            'restock'
        );

        $order = app(OrderService::class)->createDraftOrder($customer->id, $warehouse->id);
        $item = app(OrderService::class)->addItem($order, $product, 2);

        $response = $this->patchJson("/api/v1/orders/items/{$item->id}", [
            'quantity' => 4,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Item updated')
            ->assertJsonPath('item.quantity', 4)
            ->assertJsonPath('order.total', '80.00');

        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'quantity' => 4,
        ]);

        $this->assertDatabaseHas('stock_reservations', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 4,
        ]);

        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'type' => 'item_updated',
        ]);
    }

    public function test_order_api_removes_item_through_domain_service()
    {
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create([
            'price' => 15,
            'cost_price' => 6,
        ]);

        app(ProductService::class)->adjustStock(
            $product,
            $warehouse->id,
            'in',
            10,
            'restock'
        );

        $order = app(OrderService::class)->createDraftOrder($customer->id, $warehouse->id);
        $item = app(OrderService::class)->addItem($order, $product, 3);

        $response = $this->deleteJson("/api/v1/orders/items/{$item->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Item removed')
            ->assertJsonPath('order.total', '0.00');

        $this->assertDatabaseMissing('order_items', [
            'id' => $item->id,
        ]);

        $this->assertDatabaseMissing('stock_reservations', [
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseHas('order_activities', [
            'order_id' => $order->id,
            'type' => 'item_removed',
        ]);
    }

    public function test_order_api_index_returns_only_current_company_orders()
    {
        $this->actingAsUser('sales');

        $ownOrder = Order::factory()->create();
        $otherCompany = Company::create([
            'name' => 'Second Company',
            'slug' => 'second-company',
            'is_active' => true,
        ]);
        Order::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->getJson('/api/v1/orders');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($ownOrder->id));
        $this->assertCount(1, $ids);
    }

    public function test_order_api_denies_access_to_foreign_company_order()
    {
        $user = $this->actingAsUser('sales');
        $otherCompany = Company::create([
            'name' => 'Foreign Company',
            'slug' => 'foreign-company',
            'is_active' => true,
        ]);

        $foreignOrder = Order::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->getJson("/api/v1/orders/{$foreignOrder->id}");

        $response->assertForbidden();
        $this->assertNotEquals($user->company_id, $foreignOrder->company_id);
    }

    public function test_order_api_denies_mutating_foreign_company_order()
    {
        $this->actingAsUser('sales');

        $ownWarehouse = Warehouse::factory()->create();
        $otherCompany = Company::create([
            'name' => 'External Company',
            'slug' => 'external-company',
            'is_active' => true,
        ]);
        $customer = Customer::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        $foreignOrder = Order::factory()->create([
            'company_id' => $otherCompany->id,
            'customer_id' => $customer->id,
            'warehouse_id' => Warehouse::factory()->create([
                'company_id' => $otherCompany->id,
            ])->id,
        ]);
        $ownProduct = Product::factory()->create();

        app(ProductService::class)->adjustStock(
            $ownProduct,
            $ownWarehouse->id,
            'in',
            10,
            'restock'
        );

        $response = $this->postJson("/api/v1/orders/{$foreignOrder->id}/items", [
            'product_id' => $ownProduct->id,
            'quantity' => 1,
        ]);

        $response->assertForbidden();
    }
}

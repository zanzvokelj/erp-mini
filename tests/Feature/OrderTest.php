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
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_confirmation_does_not_reduce_stock_but_reserves()
    {
        $user = User::factory()->sales()->create();
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
            'quantity' => 5,
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

        // ✅ reservation (NOW REQUIRES warehouse_id)
        app(InventoryService::class)->reserveStock(
            $product,
            $order->id,
            5,
            $warehouse->id
        );

        app(OrderService::class)->confirmOrder($order);

        // STOCK ostane isti
        $stock = app(ProductService::class)
            ->calculateCurrentStock($product);

        $this->assertEquals(100, $stock);

        // AVAILABLE se zmanjša
        $available = app(InventoryService::class)
            ->availableStock($product, $warehouse->id);

        $this->assertEquals(95, $available);
    }

    public function test_order_cannot_exceed_available_stock()
    {
        $user = User::factory()->sales()->create();
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
            'quantity' => 5,
            'price_at_time' => 10,
            'cost_at_time' => 5
        ]);

        // stock = 5
        app(ProductService::class)->adjustStock(
            $product,
            $warehouse->id,
            'in',
            5,
            'restock'
        );

        // reservation = 5
        app(InventoryService::class)->reserveStock(
            $product,
            $order->id,
            5,
            $warehouse->id
        );

        // 🔥 change quantity in DB
        $order->items()->update([
            'quantity' => 10
        ]);

        // 🔥 KLJUČNO
        $order->refresh();

        $this->expectException(\Exception::class);

        app(OrderService::class)->confirmOrder($order);
    }

    public function test_shipping_reduces_stock()
    {
        $this->seed(AccountingSeeder::class);

        $user = User::factory()->sales()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        app(ProductService::class)->adjustStock(
            $product,
            $warehouse->id,
            'in',
            10,
            'restock'
        );

        $orderService = app(OrderService::class);

        $order = $orderService->createDraftOrder($customer->id, $warehouse->id);
        $orderService->addItem($order, $product, 5);
        $orderService->confirmOrder($order);
        $orderService->shipOrder($order);

        $stock = app(ProductService::class)
            ->calculateCurrentStock($product);

        $this->assertEquals(5, $stock);
    }

    public function test_order_fails_if_one_item_invalid()
    {
        $user = User::factory()->sales()->create();
        $this->actingAs($user);

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();

        app(ProductService::class)->adjustStock($product1, $warehouse->id, 'in', 10, 'restock');
        app(ProductService::class)->adjustStock($product2, $warehouse->id, 'in', 1, 'restock');

        $orderService = app(OrderService::class);

        $order = $orderService->createDraftOrder($customer->id, $warehouse->id);

        $orderService->addItem($order, $product1, 5);

        // this should fail
        $this->expectException(\Exception::class);

        $orderService->addItem($order, $product2, 5);
    }
}

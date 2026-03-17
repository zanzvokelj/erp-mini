<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;
use App\Models\Warehouse;
use App\Services\ProductService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservationEdgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_cannot_exceed_stock()
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $order = Order::factory()->create([
            'warehouse_id' => $warehouse->id
        ]);

        $productService = app(ProductService::class);
        $inventoryService = app(InventoryService::class);

        $productService->adjustStock($product, $warehouse->id, 'in', 5);

        $this->expectException(\Exception::class);

        $inventoryService->reserveStock($product, $order->id, 10, $warehouse->id);
    }

    public function test_release_reservation_restores_available_stock()
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $order = Order::factory()->create([
            'warehouse_id' => $warehouse->id
        ]);

        $productService = app(ProductService::class);
        $inventoryService = app(InventoryService::class);

        $productService->adjustStock($product, $warehouse->id, 'in', 100);

        $inventoryService->reserveStock($product, $order->id, 20, $warehouse->id);

        $inventoryService->releaseReservation($order->id);

        $available = $inventoryService->availableStock($product);

        $this->assertEquals(100, $available);
    }

    public function test_expired_reservation_releases_stock()
    {
        $product = Product::factory()->create();
        $warehouse = \App\Models\Warehouse::factory()->create();
        $order = \App\Models\Order::factory()->create([
            'warehouse_id' => $warehouse->id
        ]);

        $reservation = \App\Models\StockReservation::create([
            'product_id' => $product->id,
            'order_id' => $order->id, // ✅ FIX
            'warehouse_id' => $warehouse->id, // ✅ tudi to
            'quantity' => 5,
            'expires_at' => now()->subMinute()
        ]);

        app(\App\Services\InventoryService::class)
            ->releaseExpiredReservations();

        $this->assertDatabaseMissing('stock_reservations', [
            'id' => $reservation->id
        ]);
    }
}

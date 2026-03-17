<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_reduces_available_stock()
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $warehouse = Warehouse::factory()->create(); // ✅ FIX

        $productService = app(ProductService::class);
        $inventoryService = app(InventoryService::class);

        $productService->adjustStock(
            $product,
            $warehouse->id, // ✅ FIX
            'in',
            100
        );

        $inventoryService->reserveStock($product, $order->id, 10, $warehouse->id);

        $available = $inventoryService->availableStock($product);

        $this->assertEquals(90, $available);
    }
}

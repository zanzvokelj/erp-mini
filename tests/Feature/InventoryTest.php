<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_is_tracked_per_warehouse()
    {
        $product = Product::factory()->create();

        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $service = app(ProductService::class);

        $service->adjustStock($product,$warehouseA->id,'in',100);
        $service->adjustStock($product,$warehouseB->id,'in',50);

        $stockA = $service->calculateStockInWarehouse($product,$warehouseA->id);
        $stockB = $service->calculateStockInWarehouse($product,$warehouseB->id);

        $this->assertEquals(100,$stockA);
        $this->assertEquals(50,$stockB);
    }
}

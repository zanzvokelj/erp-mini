<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MultiWarehouseTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_is_isolated_per_warehouse()
    {
        $product = Product::factory()->create();

        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();

        $productService = app(ProductService::class);

        $productService->adjustStock($product, $warehouseA->id, 'in', 100);
        $productService->adjustStock($product, $warehouseB->id, 'in', 50);

        $stockA = $productService->calculateStockInWarehouse($product, $warehouseA->id);
        $stockB = $productService->calculateStockInWarehouse($product, $warehouseB->id);

        $this->assertEquals(100, $stockA);
        $this->assertEquals(50, $stockB);
    }


}

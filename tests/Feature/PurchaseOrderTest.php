<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_receiving_increases_stock()
    {
        $warehouse = Warehouse::factory()->create();

        $supplier = Supplier::factory()->create();

        $product = Product::factory()->create();

        $po = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'status' => 'ordered'
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => 50,
            'cost_price' => 10
        ]);

        app(PurchaseOrderService::class)->receive($po);

        $stock = app(ProductService::class)
            ->calculateCurrentStock($product);

        $this->assertEquals(50, $stock);
    }
}

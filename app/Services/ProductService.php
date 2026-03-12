<?php

namespace App\Services;
use App\Jobs\LowStockAlertJob;
use App\Models\Product;
use App\Models\StockMovement;

class ProductService
{
    public function calculateCurrentStock(Product $product): int
    {
        $stock = StockMovement::where('product_id', $product->id)
            ->selectRaw("
            SUM(
                CASE
                    WHEN type = 'in' THEN quantity
                    WHEN type = 'out' THEN -quantity
                    ELSE quantity
                END
            ) as stock
        ")
            ->value('stock');

        return $stock ?? 0;
    }

    public function calculateStockForProducts(array $productIds)
    {
        return StockMovement::whereIn('product_id', $productIds)
            ->selectRaw("
            product_id,
            SUM(
                CASE
                    WHEN type = 'in' THEN quantity
                    WHEN type = 'out' THEN -quantity
                    ELSE quantity
                END
            ) as stock
        ")
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');
    }

    public function adjustStock(
        Product $product,
        int $warehouseId,
        string $type,
        int $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null
    ): StockMovement {

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouseId,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => $userId,
        ]);

        LowStockAlertJob::dispatch($product)
            ->afterResponse();

        return $movement;
    }

    public function checkLowStock(Product $product): bool
    {
        $currentStock = $this->calculateCurrentStock($product);

        return $currentStock < $product->min_stock;
    }


    public function calculateStockInWarehouse(Product $product, int $warehouseId): int
    {
        $stock = StockMovement::where('product_id', $product->id)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw("
            SUM(
                CASE
                    WHEN type = 'in' THEN quantity
                    WHEN type = 'out' THEN -quantity
                    ELSE quantity
                END
            ) as stock
        ")
            ->value('stock');

        return $stock ?? 0;
    }
}

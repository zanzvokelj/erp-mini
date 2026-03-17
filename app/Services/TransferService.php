<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;

class TransferService
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function transfer(
        Product $product,
        int $fromWarehouse,
        int $toWarehouse,
        int $quantity
    ): WarehouseTransfer {

        if ($fromWarehouse === $toWarehouse) {
            throw new \Exception('Cannot transfer within the same warehouse.');
        }

        return DB::transaction(function () use ($product, $fromWarehouse, $toWarehouse, $quantity) {

            // 🔒 LOCK PRODUCT
            $product = Product::where('id', $product->id)
                ->lockForUpdate()
                ->first();

            // ✅ AVAILABLE (correct logic)
            $available = app(\App\Services\InventoryService::class)
                ->availableStock($product, $fromWarehouse);

            if ($available < $quantity) {
                throw new \Exception('Not enough available stock in source warehouse.');
            }

            // 📦 CREATE TRANSFER FIRST
            $transfer = WarehouseTransfer::create([
                'product_id' => $product->id,
                'from_warehouse_id' => $fromWarehouse,
                'to_warehouse_id' => $toWarehouse,
                'quantity' => $quantity
            ]);

            // ⬇ OUT
            $this->productService->adjustStock(
                $product,
                $fromWarehouse,
                'out',
                $quantity,
                'transfer',
                $transfer->id
            );

            // ⬆ IN
            $this->productService->adjustStock(
                $product,
                $toWarehouse,
                'in',
                $quantity,
                'transfer',
                $transfer->id
            );

            return $transfer;
        });
    }
}

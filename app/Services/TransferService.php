<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;

class TransferService
{
    protected ProductService $productService;
    protected CompanyGuard $companyGuard;

    public function __construct(ProductService $productService, CompanyGuard $companyGuard)
    {
        $this->productService = $productService;
        $this->companyGuard = $companyGuard;
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
            $fromWarehouseModel = Warehouse::query()->findOrFail($fromWarehouse);
            $toWarehouseModel = Warehouse::query()->findOrFail($toWarehouse);

            // 🔒 LOCK PRODUCT
            $product = Product::where('id', $product->id)
                ->lockForUpdate()
                ->first();

            $this->companyGuard->assertSameCompany(
                [$product, $fromWarehouseModel, $toWarehouseModel],
                'Transfer entities must belong to the same company.'
            );

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

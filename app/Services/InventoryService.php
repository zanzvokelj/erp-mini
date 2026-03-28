<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockReservation;
use App\Models\StockMovement;

class InventoryService
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function reservedStock(Product $product): int
    {
        return StockReservation::where('product_id', $product->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->sum('quantity');
    }

    public function availableStock(Product $product, ?int $warehouseId = null): int
    {
        $current = $warehouseId
            ? $this->productService->calculateStockInWarehouse($product, $warehouseId)
            : $this->productService->calculateCurrentStock($product);

        $reserved = StockReservation::where('product_id', $product->id)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->sum('quantity');

        return $current - $reserved;
    }

    public function reserveStock(Product $product, int $orderId, int $quantity, int $warehouseId)
    {
        $available = $this->availableStock($product, $warehouseId);

        if ($quantity > $available) {
            throw new \Exception("Not enough stock to reserve.");
        }

        StockReservation::create([
            'product_id' => $product->id,
            'order_id' => $orderId,
            'warehouse_id' => $warehouseId, // 🔥 TO JE KLJUČNO
            'quantity' => $quantity,
            'expires_at' => now()->addMinutes(30)
        ]);
    }

    public function updateReservation(int $orderId, int $productId, int $quantity): void
    {
        StockReservation::where('order_id', $orderId)
            ->where('product_id', $productId)
            ->update([
                'quantity' => $quantity
            ]);
    }

    public function releaseReservation(int $orderId): void
    {
        StockReservation::where('order_id', $orderId)->delete();
    }

    public function releaseExpiredReservations(): void
    {
        StockReservation::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();
    }

    public function adjustStock(
        Product $product,
        string $type,
        int $quantity,
        ?int $warehouseId = null,
        ?int $userId = null
    ): StockMovement {
        return $this->productService->adjustStock(
            $product,
            $warehouseId,
            $type,
            $quantity,
            'manual_adjustment',
            null,
            $userId
        );
    }
}

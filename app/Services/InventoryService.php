<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockReservation;

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

    public function availableStock(Product $product): int
    {
        $current = $this->productService
            ->calculateCurrentStock($product);

        $reserved = $this->reservedStock($product);

        return $current - $reserved;
    }

    public function reserveStock(Product $product, int $orderId, int $quantity): void
    {
        StockReservation::create([
            'product_id' => $product->id,
            'order_id' => $orderId,
            'quantity' => $quantity,
            'expires_at' => now()->addMinutes(30) // reservation TTL
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
}

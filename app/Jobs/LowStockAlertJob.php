<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LowStockAlertJob implements ShouldQueue
{
    use Queueable;

    protected Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function handle(ProductService $productService): void
    {
        $currentStock = $productService->calculateCurrentStock($this->product);

        if ($currentStock < $this->product->min_stock) {
            logger()->warning(
                "Low stock alert for product {$this->product->name}"
            );
        }
    }
}

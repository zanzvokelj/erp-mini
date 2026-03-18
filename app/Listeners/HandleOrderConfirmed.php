<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Jobs\LowStockAlertJob;
use Illuminate\Support\Facades\Log;
class HandleOrderConfirmed
{
    public function __invoke(OrderConfirmed $event): void
    {
        Log::info("ORDER CONFIRMED LISTENER RUN " . uniqid());

        $products = $event->order->items
            ->pluck('product')
            ->unique('id');

        foreach ($products as $product) {
            LowStockAlertJob::dispatch($product);
        }
    }
}

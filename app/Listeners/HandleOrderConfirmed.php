<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Jobs\LowStockAlertJob;
use Illuminate\Support\Facades\Log;
class HandleOrderConfirmed
{
    public function __invoke(OrderConfirmed $event): void
    {
        Log::info("OrderConfirmed listener handling order {$event->order->id}");

        $products = $event->order->items
            ->pluck('product')
            ->unique('id');

        foreach ($products as $product) {
            LowStockAlertJob::dispatch($product);
        }
    }
}

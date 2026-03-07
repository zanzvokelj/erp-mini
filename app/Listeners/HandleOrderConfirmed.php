<?php

namespace App\Listeners;

use App\Events\OrderConfirmed;
use App\Jobs\LowStockAlertJob;

class HandleOrderConfirmed
{
    public function handle(OrderConfirmed $event): void
    {
        $products = $event->order->items
            ->pluck('product')
            ->unique('id');

        foreach ($products as $product) {

            LowStockAlertJob::dispatch($product);

        }
    }
}

<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendShippingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderShipped $event): void
    {
        $order = $event->order;

        Log::info("Order {$order->id} shipped for {$order->customer->name}");
    }
}

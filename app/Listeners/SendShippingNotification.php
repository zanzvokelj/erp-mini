<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendShippingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __invoke(OrderShipped $event): void
    {
        Log::info("SHIPPING LISTENER RUN " . uniqid());
    }
}

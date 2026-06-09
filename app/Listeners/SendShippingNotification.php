<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendShippingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __invoke(OrderShipped $event): void
    {
        // Placeholder for real notification dispatch.
    }
}

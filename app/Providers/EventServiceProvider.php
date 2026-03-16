<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Events\OrderConfirmed;
use App\Listeners\HandleOrderConfirmed;

use App\Events\OrderShipped;
use App\Listeners\SendShippingNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        OrderConfirmed::class => [
            HandleOrderConfirmed::class,
        ],

        OrderShipped::class => [
            SendShippingNotification::class,
            \App\Listeners\GenerateInvoiceFromOrder::class,
        ],

    ];

    public function shouldDiscoverEvents()
    {
        return false;
    }
}

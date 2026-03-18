<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Events\OrderConfirmed;
use App\Listeners\HandleOrderConfirmed;

use App\Events\OrderShipped;
use App\Listeners\SendShippingNotification;

class EventServiceProvider extends ServiceProvider
{
    protected static $shouldDiscoverEvents = false;

    protected $listen = [
        OrderConfirmed::class => [
            HandleOrderConfirmed::class,
        ],
    ];
}

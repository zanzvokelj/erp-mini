<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use App\Events\OrderConfirmed;
use App\Listeners\HandleOrderConfirmed;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        OrderConfirmed::class => [
            HandleOrderConfirmed::class,
        ],

    ];
}

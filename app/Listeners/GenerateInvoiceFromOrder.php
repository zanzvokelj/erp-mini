<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Services\InvoiceService;

class GenerateInvoiceFromOrder
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function handle(OrderShipped $event): void
    {
        $order = $event->order;

        $this->invoiceService->generateFromOrder($order);
    }
}

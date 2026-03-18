<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Services\InvoiceService;
use App\Services\OrderService;

class GenerateInvoiceFromOrder
{
    protected InvoiceService $invoiceService;
    protected OrderService $orderService;

    public function __construct(
        InvoiceService $invoiceService,
        OrderService $orderService
    ) {
        $this->invoiceService = $invoiceService;
        $this->orderService = $orderService;
    }

    public function __invoke(OrderShipped $event): void
    {
        $order = $event->order;

        if ($order->invoice()->exists()) {
            return;
        }

        $invoice = $this->invoiceService->generateFromOrder($order);

        $this->orderService->logActivity(
            $order,
            'invoice_created',
            "Invoice {$invoice->invoice_number} created"
        );

        \Log::info('Invoice listener fired');
    }
}

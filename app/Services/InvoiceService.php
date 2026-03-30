<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected CompanyGuard $companyGuard
    ) {}

    public function generateFromOrder(Order $order, float $taxRate = 0): Invoice
    {
        $invoice = DB::transaction(function () use ($order, $taxRate) {

            $order->load('items.product', 'customer');

            $this->companyGuard->assertSameCompany(
                [$order, $order->customer, ...$order->items->pluck('product')->all()],
                'Invoice can only be generated from same-company order data.'
            );

            $subtotal = round((float) $order->subtotal, 2);
            $tax = round($subtotal * ($taxRate / 100), 2);
            $total = round($subtotal + $tax, 2);

            $invoice = Invoice::create([
                'company_id' => $order->company_id,
                'invoice_number' => 'INV-' . uniqid(),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'issued_at' => now(),
                'due_date' => now()->addDays(14)
            ]);

            foreach ($order->items as $item) {

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price_at_time,
                    'subtotal' => $item->price_at_time * $item->quantity
                ]);

            }

            return $invoice;
        });

        $this->accountingService->recordInvoiceIssued($invoice);

        return $invoice;
    }
}

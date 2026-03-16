<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateFromOrder(Order $order): Invoice
    {
        return DB::transaction(function () use ($order) {

            $order->load('items.product', 'customer');

            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . uniqid(),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'status' => 'draft',
                'subtotal' => $order->subtotal,
                'tax' => 0,
                'total' => $order->total,
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
    }
}

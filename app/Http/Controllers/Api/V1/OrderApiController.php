<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Support\Str;

class OrderApiController extends Controller
{
    public function index()
    {
        return OrderResource::collection(
            Order::with(['customer','items.product'])
                ->latest()
                ->paginate(20)
        );
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'order',
            'items.product',
            'payments'
        ]);

        return response()->json($invoice);
    }

    public function invoicable()
    {
        return Order::with('customer')
            ->where('status', 'shipped')
            ->doesntHave('invoice')
            ->latest()
            ->limit(50)
            ->get([
                'id',
                'order_number',
                'customer_id',
                'total'
            ]);
    }

    public function ship(Order $order)
    {
        $order->load('items.product');

        if ($order->invoice) {
            return response()->json([
                'message' => 'Invoice already exists'
            ], 400);
        }

        $invoice = Invoice::create([
            'invoice_number' => 'INV-' . Str::random(12),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'status' => 'draft',
            'subtotal' => $order->subtotal,
            'tax' => 0,
            'total' => $order->total,
            'issued_at' => now()
        ]);

        foreach ($order->items as $item) {

            $invoice->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal
            ]);

        }

        return response()->json([
            'message' => 'Invoice created',
            'invoice_id' => $invoice->id
        ]);
    }
}

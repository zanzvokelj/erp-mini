<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Warehouse;
use App\Services\OrderService;
use Illuminate\Http\Request;
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

    public function show(Order $order)
    {
        $order->load([
            'customer',
            'items.product',
        ]);

        return new OrderResource($order);
    }


    public function store(Request $request, OrderService $orderService)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id', // ✅ DODAJ
        ]);

        $order = $orderService->createDraftOrder(
            $request->customer_id,
            $request->warehouse_id // ✅ UPORABI REQUEST
        );

        return response()->json($order, 201);
    }

    public function invoicable(Request $request)
    {
        $query = Order::with('customer')
            ->where('status', 'shipped')
            ->doesntHave('invoice');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('customer', function ($q2) use ($request) {
                        $q2->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        return $query->latest()
            ->limit(20)
            ->get()
            ->map(function ($o) {
                return [
                    'id' => $o->id,
                    'label' => "{$o->order_number} — {$o->customer->name} — €" . number_format($o->total,2)
                ];
            });
    }

    public function ship(Order $order)
    {
        if ($order->status !== 'confirmed') {
            return response()->json([
                'error' => 'Only confirmed orders can be shipped'
            ], 422);
        }

        $order->update([
            'status' => 'shipped'
        ]);

        return response()->json([
            'message' => 'Order shipped successfully'
        ]);
    }


    public function createInvoice(Order $order)
    {
        if ($order->status !== 'shipped') {
            return response()->json([
                'error' => 'Order must be shipped before invoicing'
            ], 422);
        }

        if ($order->invoice) {
            return response()->json([
                'error' => 'Invoice already exists'
            ], 400);
        }

        $order->load('items.product');

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
                'price' => $item->price_at_time,
                'subtotal' => $item->price_at_time * $item->quantity
            ]);
        }

        return response()->json([
            'message' => 'Invoice created',
            'invoice_id' => $invoice->id
        ]);
    }


}

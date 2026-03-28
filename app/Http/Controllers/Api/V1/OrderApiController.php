<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected InvoiceService $invoiceService
    ) {}

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


    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $order = $this->orderService->createDraftOrder(
            (int) $validated['customer_id'],
            (int) $validated['warehouse_id']
        );

        return response()->json($order, 201);
    }

    public function addItem(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $product = Product::findOrFail($validated['product_id']);
            $item = $this->orderService->addItem(
                $order,
                $product,
                (int) $validated['quantity']
            );

            $item->load('product');
            $order->load('customer', 'items.product');

            return response()->json([
                'message' => 'Item added',
                'item' => $item,
                'order' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateItem(Request $request, OrderItem $item)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $item = $this->orderService->updateItem($item, (int) $validated['quantity']);
            $order = $item->order->fresh();
            $order->load('customer', 'items.product');

            return response()->json([
                'message' => 'Item updated',
                'item' => $item,
                'order' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function removeItem(OrderItem $item)
    {
        try {
            $order = $item->order;
            $this->orderService->removeItem($item);
            $order = $order->fresh();
            $order->load('customer', 'items.product');

            return response()->json([
                'message' => 'Item removed',
                'order' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function confirm(Order $order)
    {
        try {
            $this->orderService->confirmOrder($order);

            $order->load('customer', 'items.product');

            return response()->json([
                'message' => 'Order confirmed',
                'order' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
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
        try {
            $this->orderService->shipOrder($order);

            $order->load('customer', 'items.product');

            return response()->json([
                'message' => 'Order shipped successfully',
                'order' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function complete(Order $order)
    {
        try {
            $this->orderService->completeOrder($order);

            $order->load('customer', 'items.product');

            return response()->json([
                'message' => 'Order completed',
                'order' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function cancel(Order $order)
    {
        try {
            $this->orderService->cancelOrder($order);

            $order->load('customer', 'items.product');

            return response()->json([
                'message' => 'Order cancelled',
                'order' => new OrderResource($order),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function createInvoice(Request $request, Order $order)
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

        $request->validate([
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $invoice = $this->invoiceService->generateFromOrder(
            $order,
            (float) $request->input('tax_rate', 0)
        );

        return response()->json([
            'message' => 'Invoice created',
            'invoice_id' => $invoice->id
        ]);
    }
}

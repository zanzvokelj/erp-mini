<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected InventoryService $inventoryService,
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


    public function store(Request $request, OrderService $orderService)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $order = $orderService->createDraftOrder(
            $request->customer_id,
            $request->warehouse_id
        );

        return response()->json($order, 201);
    }

    public function addItem(Request $request, Order $order)
    {
        if ($order->status !== 'draft') {
            return response()->json([
                'error' => 'Items can only be added to draft orders.',
            ], 422);
        }

        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($request->product_id);

        $available = $this->inventoryService
            ->availableStock($product, $order->warehouse_id);

        if ($request->quantity > $available) {
            return response()->json([
                'error' => "Only {$available} items available in stock.",
            ], 422);
        }

        $item = $this->orderService->addItem(
            $order,
            $product,
            (int) $request->quantity
        );

        $this->orderService->calculateTotals($order);

        $item->load('product');
        $order->load('customer', 'items.product');

        return response()->json([
            'message' => 'Item added',
            'item' => $item,
            'order' => new OrderResource($order),
        ]);
    }

    public function updateItem(Request $request, OrderItem $item)
    {
        $order = $item->order;

        if ($order->status !== 'draft') {
            return response()->json([
                'error' => 'Only draft orders can be edited.',
            ], 422);
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item->update([
            'quantity' => (int) $request->quantity,
        ]);

        $this->inventoryService->updateReservation(
            $order->id,
            $item->product_id,
            (int) $request->quantity
        );

        $this->orderService->calculateTotals($order);

        $this->orderService->logActivity(
            $order,
            'item_updated',
            "{$item->product->name} quantity updated to {$request->quantity}"
        );

        $item->load('product');
        $order->load('customer', 'items.product');

        return response()->json([
            'message' => 'Item updated',
            'item' => $item,
            'order' => new OrderResource($order),
        ]);
    }

    public function removeItem(OrderItem $item)
    {
        $order = $item->order;

        if ($order->status !== 'draft') {
            return response()->json([
                'error' => 'Only draft orders can be edited.',
            ], 422);
        }

        $productName = $item->product->name;

        \App\Models\StockReservation::where('order_id', $order->id)
            ->where('product_id', $item->product_id)
            ->delete();

        $item->delete();

        $this->orderService->calculateTotals($order);

        $this->orderService->logActivity(
            $order,
            'item_removed',
            "{$productName} removed from order"
        );

        $order->load('customer', 'items.product');

        return response()->json([
            'message' => 'Item removed',
            'order' => new OrderResource($order),
        ]);
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

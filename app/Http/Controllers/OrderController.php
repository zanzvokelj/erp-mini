<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\OrderItem;
use App\Services\InventoryService;
use App\Models\Warehouse;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected InventoryService $inventoryService
    ) {}

    public function index()
    {
        $query = Order::with(['customer','warehouse']);

        /*
        SEARCH
        */

        if(request('search')) {
            $query->where('order_number','like','%'.request('search').'%');
        }

        /*
        CUSTOMER FILTER
        */

        if(request('customer')) {
            $query->where('customer_id', request('customer'));
        }

        /*
        STATUS FILTER
        */

        if(request('status')) {
            $query->where('status', request('status'));
        }

        $orders = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $customers = \App\Models\Customer::all();

        return view('orders.index', compact('orders','customers'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => ['required','exists:customers,id'],
            'warehouse_id' => ['required','exists:warehouses,id']
        ]);

        $order = $this->orderService->createDraftOrder(
            $request->customer_id,
            $request->warehouse_id
        );

        return redirect()->route('orders.show', $order);
    }

    public function confirm(Order $order)
    {
        $this->orderService->confirmOrder($order);

        return redirect()->back()
            ->with('success', 'Order confirmed');
    }

    public function show(Order $order)
    {
        $order->load('customer', 'warehouse', 'items.product', 'activities');

        return view('orders.show', compact('order'));
    }


    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('orders.create', compact('customers','warehouses'));
    }

    public function addItem(Request $request, Order $order)
    {
        if ($order->status !== 'draft') {
            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'Items can only be added to draft orders.');
        }

        $request->validate([
            'product_id' => ['required','exists:products,id'],
            'quantity' => ['required','integer','min:1']
        ]);

        $product = Product::findOrFail($request->product_id);

        $available = $this->inventoryService
            ->availableStock($product, $order->warehouse_id);

        if ($request->quantity > $available) {

            return back()->with('error',
                "Only {$available} items available in stock."
            );

        }

        $this->orderService->addItem(
            $order,
            $product,
            $request->quantity
        );

        $this->orderService->calculateTotals($order);

        return redirect()->route('orders.show', $order);
    }

    public function ship(Order $order)
    {
        try {

            $this->orderService->shipOrder($order);

            return back()->with('success', 'Order shipped');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }
    }

    public function complete(Order $order)
    {
        if ($order->status !== 'shipped') {
            return back()->with('error', 'Only shipped orders can be completed.');
        }

        $order->update([
            'status' => 'completed'
        ]);

        $this->orderService->logActivity($order, 'completed', 'Order completed');

        return back()->with('success', 'Order completed');
    }


    public function updateItem(Request $request, OrderItem $item)
    {
        $order = $item->order;

        if ($order->status !== 'draft') {
            return back()->with('error','Only draft orders can be edited.');
        }

        $request->validate([
            'quantity' => ['required','integer','min:1']
        ]);

        $item->update([
            'quantity' => $request->quantity
        ]);

        // UPDATE RESERVATION
        $this->inventoryService->updateReservation(
            $order->id,
            $item->product_id,
            $request->quantity
        );

        $this->orderService->calculateTotals($order);

        $this->orderService->logActivity(
            $order,
            'item_updated',
            "{$item->product->name} quantity updated to {$request->quantity}"
        );

        return back();
    }

    public function removeItem(OrderItem $item)
    {
        $order = $item->order;

        if ($order->status !== 'draft') {
            return back()->with('error','Only draft orders can be edited.');
        }

        $productName = $item->product->name;

        // release reservation for this item
        \App\Models\StockReservation::where('order_id',$order->id)
            ->where('product_id',$item->product_id)
            ->delete();

        $item->delete();

        $this->orderService->calculateTotals($order);

        $this->orderService->logActivity(
            $order,
            'item_removed',
            "{$productName} removed from order"
        );

        return back();
    }

    public function cancel(Order $order)
    {
        if (!in_array($order->status, ['draft','confirmed'])) {
            return back()->with('error','Only draft or confirmed orders can be cancelled.');
        }

        $this->orderService->cancelOrder($order);

        return back()->with('success','Order cancelled');
    }

    public function returnOrder(Order $order)
    {
        try {

            $this->orderService->returnOrder($order);

            return back()->with('success', 'Order returned');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }
    }

    public function export()
    {
        $orders = \App\Models\Order::with('customer')->get();

        $filename = 'orders.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // header
            fputcsv($file, [
                'Order Number',
                'Customer',
                'Status',
                'Total',
                'Date'
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->customer->name ?? '',
                    $order->status,
                    $order->total,
                    $order->created_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Warehouse;
use App\Services\CompanyContext;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index()
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::with(['customer','warehouse'])
            ->where('company_id', app(CompanyContext::class)->id());

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

        $customers = \App\Models\Customer::query()
            ->where('company_id', app(CompanyContext::class)->id())
            ->get();

        return view('orders.index', compact('orders','customers'));
    }
    public function store(Request $request)
    {
        $this->authorize('create', Order::class);

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
        $this->authorize('confirm', $order);

        $this->orderService->confirmOrder($order);

        return redirect()->back()
            ->with('success', 'Order confirmed');
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load('customer', 'warehouse', 'items.product', 'activities');

        return view('orders.show', compact('order'));
    }


    public function create()
    {
        $this->authorize('create', Order::class);

        $warehouses = Warehouse::query()
            ->where('company_id', app(CompanyContext::class)->id())
            ->orderBy('name')
            ->get();

        return view('orders.create', compact('warehouses'));
    }

    public function addItem(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $validated = $request->validate([
            'product_id' => ['required','exists:products,id'],
            'quantity' => ['required','integer','min:1']
        ]);

        try {
            $product = Product::findOrFail($validated['product_id']);

            $this->orderService->addItem(
                $order,
                $product,
                (int) $validated['quantity']
            );

            return redirect()->route('orders.show', $order);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ship(Order $order)
    {
        $this->authorize('ship', $order);

        try {

            $this->orderService->shipOrder($order);

            return back()->with('success', 'Order shipped');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }
    }

    public function complete(Order $order)
    {
        $this->authorize('complete', $order);

        try {
            $this->orderService->completeOrder($order);

            return back()->with('success', 'Order completed');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }


    public function updateItem(Request $request, OrderItem $item)
    {
        $this->authorize('update', $item->order);

        $validated = $request->validate([
            'quantity' => ['required','integer','min:1']
        ]);

        try {
            $this->orderService->updateItem($item, (int) $validated['quantity']);

            return back()->with('success', 'Item updated');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function removeItem(OrderItem $item)
    {
        $this->authorize('update', $item->order);

        try {
            $this->orderService->removeItem($item);

            return back()->with('success', 'Item removed');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Order $order)
    {
        $this->authorize('cancel', $order);

        try {
            $this->orderService->cancelOrder($order);

            return back()->with('success','Order cancelled');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function returnOrder(Order $order)
    {
        $this->authorize('returnOrder', $order);

        try {

            $this->orderService->returnOrder($order);

            return back()->with('success', 'Order returned');

        } catch (\Exception $e) {

            return back()->with('error', $e->getMessage());

        }
    }

    public function export()
    {
        $this->authorize('viewAny', Order::class);

        $orders = \App\Models\Order::with('customer')
            ->where('company_id', app(CompanyContext::class)->id())
            ->get();

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

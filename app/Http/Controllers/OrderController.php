<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;


class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index()
    {
        $query = Order::with('customer');

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
            'customer_id' => ['required','exists:customers,id']
        ]);

        $order = $this->orderService->createDraftOrder(
            $request->customer_id
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
        $order->load('customer', 'items.product', 'activities');

        return view('orders.show', compact('order'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('orders.create', compact('customers'));
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
        if ($order->status !== 'confirmed') {
            return back()->with('error', 'Only confirmed orders can be shipped.');
        }

        $order->update([
            'status' => 'shipped'
        ]);

        $this->orderService->logActivity($order, 'shipped', 'Order shipped');

        return back()->with('success', 'Order shipped');
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
        if ($order->status !== 'confirmed') {
            return back()->with('error','Only confirmed orders can be cancelled.');
        }

        $this->orderService->cancelOrder($order);

        return back()->with('success','Order cancelled');
    }


}

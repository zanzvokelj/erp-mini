<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Services\PurchaseOrderService;
use App\Models\PurchaseOrderItem;
use App\Models\Warehouse;



class PurchaseOrderController extends Controller
{
    protected PurchaseOrderService $service;

    public function __construct(PurchaseOrderService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier','warehouse'])
            ->latest()
            ->paginate(20);

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('purchase-orders.create', compact('suppliers','warehouses'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => ['required','exists:suppliers,id'],
            'warehouse_id' => ['required','exists:warehouses,id']
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-' . now()->timestamp,
            'supplier_id' => $request->supplier_id,
            'warehouse_id' => $request->warehouse_id,
            'status' => 'draft'
        ]);

        return redirect()->route('purchase-orders.show', $po);
    }

    public function show(PurchaseOrder $po)
    {
        $po->load('supplier','items.product','payments');

        $products = \App\Models\Product::orderBy('name')->get();

        return view('purchase-orders.show', compact('po','products'));
    }

    public function receive(PurchaseOrder $po)
    {
        $this->service->receive($po);

        return back()->with('success','Stock received');
    }

    public function addItem(Request $request, PurchaseOrder $po)
    {
        $request->validate([
            'product_id' => ['required','exists:products,id'],
            'quantity' => ['required','integer','min:1'],
            'cost_price' => ['required','numeric']
        ]);

        $po->items()->create([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'cost_price' => $request->cost_price
        ]);

        $total = PurchaseOrderItem::where('purchase_order_id', $po->id)
            ->selectRaw('SUM(quantity * cost_price) as total')
            ->value('total');

        $po->update([
            'total' => $total
        ]);

        return back();
    }

    public function order(PurchaseOrder $po)
    {
        $po->update([
            'status' => 'ordered',
            'ordered_at' => now()
        ]);

        return back();
    }

    public function recordPayment(Request $request, PurchaseOrder $po)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string'],
        ]);

        try {
            $this->service->recordSupplierPayment(
                $po,
                (float) $request->amount,
                $request->payment_method
            );

            return back()->with('success', 'Supplier payment recorded');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

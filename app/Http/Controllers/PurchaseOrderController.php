<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Services\PurchaseOrderService;
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
        $validated = $request->validate([
            'supplier_id' => ['required','exists:suppliers,id'],
            'warehouse_id' => ['required','exists:warehouses,id'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $po = $this->service->createDraft(
            supplierId: (int) $validated['supplier_id'],
            warehouseId: (int) $validated['warehouse_id'],
            taxRate: (float) ($validated['tax_rate'] ?? 0)
        );

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
        $validated = $request->validate([
            'product_id' => ['required','exists:products,id'],
            'quantity' => ['required','integer','min:1'],
            'cost_price' => ['required','numeric']
        ]);

        $this->service->addItem(
            $po,
            (int) $validated['product_id'],
            (int) $validated['quantity'],
            (float) $validated['cost_price']
        );

        return back()->with('success', 'Item added');
    }

    public function order(PurchaseOrder $po)
    {
        $this->service->markAsOrdered($po);

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

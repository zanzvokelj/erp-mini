<?php

namespace App\Http\Controllers;

use App\Services\ReorderSuggestionService;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;

class ReorderController extends Controller
{
    public function index(ReorderSuggestionService $service)
    {
        $suggestions = $service->suggestions();

        return view('reorder.index', compact('suggestions'));
    }

    public function createPO(Request $request)
    {
        $product = Product::findOrFail($request->product_id);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-' . now()->timestamp,
            'supplier_id' => $product->supplier_id,
            'status' => 'draft',
            'total' => $request->quantity * $product->cost_price
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'cost_price' => $product->cost_price
        ]);

        return redirect()->route('purchase-orders.show',$po);
    }
}

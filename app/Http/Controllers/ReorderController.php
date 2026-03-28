<?php

namespace App\Http\Controllers;

use App\Services\ReorderSuggestionService;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\PurchaseOrderService;

class ReorderController extends Controller
{
    public function index(ReorderSuggestionService $service)
    {
        $suggestions = $service->suggestions();

        return view('reorder.index', compact('suggestions'));
    }

    public function createPO(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($request->product_id);

        $po = app(PurchaseOrderService::class)->createDraftForProduct(
            $product,
            (int) $validated['quantity']
        );

        return redirect()->route('purchase-orders.show',$po);
    }
}

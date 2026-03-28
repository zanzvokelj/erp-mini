<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'type' => ['required', 'in:in,out,adjustment'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($request->product_id);

        $this->productService->adjustStock(
            $product,
            $validated['warehouse_id'] ?? null,
            $validated['type'],
            (int) $validated['quantity'],
            'manual_adjustment',
            null,
            $request->user()?->id
        );

        return response()->json([
            'message' => 'Stock adjusted'
        ]);
    }
}

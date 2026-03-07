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
        $product = Product::findOrFail($request->product_id);

        $this->productService->adjustStock(
            $product,
            $request->type,
            $request->quantity
        );

        return response()->json([
            'message' => 'Stock adjusted'
        ]);
    }
}

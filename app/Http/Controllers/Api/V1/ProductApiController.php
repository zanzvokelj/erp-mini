<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use OpenApi\Annotations as OA;
class ProductApiController extends Controller
{
    public function index()
    {
        $query = Product::query()->with('supplier');

        if (request('search')) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('sku', 'like', '%' . request('search') . '%');
            });
        }

        if (request('supplier')) {
            $query->where('supplier_id', request('supplier'));
        }

        $perPage = request('per_page', 20);

        return ProductResource::collection(
            $query->orderBy('name')->paginate($perPage)
        );
    }

    public function show(Product $product)
    {
        $product->load('supplier');

        return new ProductResource($product);
    }

    public function stockHistory(Product $product)
    {
        $movements = StockMovement::where('product_id', $product->id)
            ->latest()
            ->limit(100)
            ->get();

        return [
            'product' => new ProductResource($product),
            'movements' => StockMovementResource::collection($movements)
        ];
    }
}

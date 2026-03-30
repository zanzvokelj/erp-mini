<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\CompanyContext;
use OpenApi\Annotations as OA;
class ProductApiController extends Controller
{
    public function index()
    {
        $query = Product::query()
            ->where('company_id', app(CompanyContext::class)->id())
            ->with('supplier');

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
        abort_if(
            (int) $product->company_id !== app(CompanyContext::class)->id(),
            404
        );

        $product->load('supplier');

        return new ProductResource($product);
    }

    public function stockHistory(Product $product)
    {
        abort_if(
            (int) $product->company_id !== app(CompanyContext::class)->id(),
            404
        );

        $movements = StockMovement::where('product_id', $product->id)
            ->where('company_id', app(CompanyContext::class)->id())
            ->latest()
            ->limit(100)
            ->get();

        return [
            'product' => new ProductResource($product),
            'movements' => StockMovementResource::collection($movements)
        ];
    }
}

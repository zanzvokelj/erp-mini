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
    /**
     * @OA\Get(
     *     path="/products",
     *     summary="List products",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search products by name or SKU",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="supplier",
     *         in="query",
     *         description="Filter by supplier ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of products"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Get product",
     *     tags={"Products"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Product details"
     *     )
     * )
     */
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

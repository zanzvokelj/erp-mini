<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Http\Request;
use App\Services\InventoryForecastService;
use App\Services\ProductQueryService;
use App\Models\Warehouse;
use App\Models\StockMovement;

class ProductController extends Controller
{
    public function index(ProductQueryService $productQuery)
    {
        $products = $productQuery->getProducts(request()->all());

        $suppliers = \DB::table('suppliers')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('products.index', compact('products', 'suppliers', 'warehouses'));
    }

    public function store(StoreProductRequest $request)
    {
        return Product::create($request->validated());
    }

    public function show(Product $product)
    {
        $movements = $product->stockMovements()
            ->with('warehouse')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse();

        $balance = 0;

        foreach ($movements as $movement) {
            if ($movement->type === 'in') {
                $balance += $movement->quantity;
            } else {
                $balance -= $movement->quantity;
            }

            $movement->balance = $balance;
        }

        $currentStock = $balance;

        $movements = $movements->reverse()->take(50);

        $forecastService = new InventoryForecastService();
        $daysUntilOut = $forecastService->forecast($product);

        $warehouses = Warehouse::orderBy('name')->get();

        $warehouseStock = StockMovement::where('product_id', $product->id)
            ->selectRaw("
                warehouse_id,
                SUM(
                    CASE
                        WHEN type = 'in' THEN quantity
                        WHEN type = 'out' THEN -quantity
                        ELSE 0
                    END
                ) as stock
            ")
            ->groupBy('warehouse_id')
            ->pluck('stock', 'warehouse_id');

        return view('products.show', [
            'product' => $product,
            'movements' => $movements,
            'stock' => $currentStock,
            'daysUntilOut' => $daysUntilOut,
            'warehouses' => $warehouses,
            'warehouseStock' => $warehouseStock
        ]);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted'
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $warehouseId = $request->input('warehouse_id');

        $products = Product::query()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%");
                });
            })
            ->limit(20)
            ->get();

        $productService = app(\App\Services\ProductService::class);

        $products = $products->map(function ($product) use ($warehouseId, $productService) {

            $stock = $warehouseId
                ? $productService->calculateStockInWarehouse($product, $warehouseId)
                : $productService->calculateCurrentStock($product);

            $reserved = \App\Models\StockReservation::where('product_id', $product->id)
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->sum('quantity');

            $available = $stock - $reserved;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'stock' => $stock,
                'reserved' => $reserved,
                'available' => $available
            ];
        });

        return response()->json($products);
    }
}

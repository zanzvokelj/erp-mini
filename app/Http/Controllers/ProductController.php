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
                $normalizedQuery = mb_strtolower(trim($query));

                $q->where(function ($sub) use ($normalizedQuery) {
                    $sub->whereRaw('LOWER(name) LIKE ?', ["%{$normalizedQuery}%"])
                        ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$normalizedQuery}%"]);
                });
            })
            ->limit(20)
            ->get();

        $productService = app(\App\Services\ProductService::class);
        $warehouses = Warehouse::orderByRaw("
                CASE
                    WHEN LOWER(name) = 'main' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('name')
            ->get();

        $products = $products->map(function ($product) use ($warehouseId, $productService, $warehouses) {

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

            $sourceWarehouse = null;

            if (! $warehouseId) {
                $sourceWarehouse = $warehouses
                    ->map(function ($warehouse) use ($product, $productService) {
                        $warehouseStock = $productService->calculateStockInWarehouse(
                            $product,
                            $warehouse->id
                        );

                        $warehouseReserved = \App\Models\StockReservation::where('product_id', $product->id)
                            ->where('warehouse_id', $warehouse->id)
                            ->where(function ($q) {
                                $q->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            })
                            ->sum('quantity');

                        $warehouseAvailable = $warehouseStock - $warehouseReserved;

                        return [
                            'id' => $warehouse->id,
                            'name' => $warehouse->name,
                            'stock' => $warehouseStock,
                            'reserved' => $warehouseReserved,
                            'available' => $warehouseAvailable,
                        ];
                    })
                    ->firstWhere('available', '>', 0);
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'stock' => $stock,
                'reserved' => $reserved,
                'available' => $available,
                'source_warehouse_id' => $sourceWarehouse['id'] ?? null,
                'source_warehouse_name' => $sourceWarehouse['name'] ?? null,
                'source_stock' => $sourceWarehouse['stock'] ?? null,
                'source_reserved' => $sourceWarehouse['reserved'] ?? null,
                'source_available' => $sourceWarehouse['available'] ?? null,
            ];
        });

        return response()->json($products);
    }
}

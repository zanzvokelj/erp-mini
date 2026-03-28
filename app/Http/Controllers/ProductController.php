<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Http\Request;
use App\Services\InventoryForecastService;
use App\Services\ProductQueryService;
use App\Models\Warehouse;
use App\Models\StockMovement;
use App\Models\StockReservation;
use Illuminate\Support\Collection;

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

        $productIds = $products->pluck('id')->all();

        $stockExpr = "
            SUM(
                CASE
                    WHEN type = 'in' THEN quantity
                    WHEN type = 'out' THEN -quantity
                    ELSE quantity
                END
            )
        ";

        $activeReservations = StockReservation::query()
            ->whereIn('product_id', $productIds)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if ($warehouseId) {
            $stockByProduct = StockMovement::query()
                ->whereIn('product_id', $productIds)
                ->where('warehouse_id', $warehouseId)
                ->selectRaw("product_id, COALESCE({$stockExpr}, 0) as stock")
                ->groupBy('product_id')
                ->pluck('stock', 'product_id');

            $reservedByProduct = (clone $activeReservations)
                ->where('warehouse_id', $warehouseId)
                ->selectRaw('product_id, SUM(quantity) as reserved')
                ->groupBy('product_id')
                ->pluck('reserved', 'product_id');

            return response()->json(
                $products->map(function ($product) use ($stockByProduct, $reservedByProduct) {
                    $stock = (int) ($stockByProduct[$product->id] ?? 0);
                    $reserved = (int) ($reservedByProduct[$product->id] ?? 0);

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'price' => $product->price,
                        'stock' => $stock,
                        'reserved' => $reserved,
                        'available' => $stock - $reserved,
                        'source_warehouse_id' => null,
                        'source_warehouse_name' => null,
                        'source_stock' => null,
                        'source_reserved' => null,
                        'source_available' => null,
                    ];
                })
            );
        }

        $stockByProduct = StockMovement::query()
            ->whereIn('product_id', $productIds)
            ->selectRaw("product_id, COALESCE({$stockExpr}, 0) as stock")
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');

        $reservedByProduct = (clone $activeReservations)
            ->selectRaw('product_id, SUM(quantity) as reserved')
            ->groupBy('product_id')
            ->pluck('reserved', 'product_id');

        $warehouses = Warehouse::orderByRaw("
                CASE
                    WHEN LOWER(name) = 'main' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('name')
            ->get();

        $warehouseStocks = StockMovement::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->selectRaw("product_id, warehouse_id, COALESCE({$stockExpr}, 0) as stock")
            ->groupBy('product_id', 'warehouse_id')
            ->get()
            ->groupBy('product_id');

        $warehouseReservations = (clone $activeReservations)
            ->whereIn('warehouse_id', $warehouses->pluck('id'))
            ->selectRaw('product_id, warehouse_id, SUM(quantity) as reserved')
            ->groupBy('product_id', 'warehouse_id')
            ->get()
            ->groupBy('product_id');

        $products = $products->map(function ($product) use (
            $stockByProduct,
            $reservedByProduct,
            $warehouseStocks,
            $warehouseReservations,
            $warehouses
        ) {
            $stock = (int) ($stockByProduct[$product->id] ?? 0);
            $reserved = (int) ($reservedByProduct[$product->id] ?? 0);
            $sourceWarehouse = $this->firstAvailableWarehouse(
                $product->id,
                $warehouses,
                $warehouseStocks,
                $warehouseReservations
            );

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'stock' => $stock,
                'reserved' => $reserved,
                'available' => $stock - $reserved,
                'source_warehouse_id' => $sourceWarehouse['id'] ?? null,
                'source_warehouse_name' => $sourceWarehouse['name'] ?? null,
                'source_stock' => $sourceWarehouse['stock'] ?? null,
                'source_reserved' => $sourceWarehouse['reserved'] ?? null,
                'source_available' => $sourceWarehouse['available'] ?? null,
            ];
        });

        return response()->json($products);
    }

    protected function firstAvailableWarehouse(
        int $productId,
        Collection $warehouses,
        Collection $warehouseStocks,
        Collection $warehouseReservations
    ): ?array {
        $productStocks = $warehouseStocks->get($productId, collect())->keyBy('warehouse_id');
        $productReservations = $warehouseReservations->get($productId, collect())->keyBy('warehouse_id');

        foreach ($warehouses as $warehouse) {
            $stock = (int) ($productStocks->get($warehouse->id)->stock ?? 0);
            $reserved = (int) ($productReservations->get($warehouse->id)->reserved ?? 0);
            $available = $stock - $reserved;

            if ($available > 0) {
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'stock' => $stock,
                    'reserved' => $reserved,
                    'available' => $available,
                ];
            }
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\InventoryForecastService;
use App\Models\Warehouse;
use App\Models\StockMovement;

class ProductController extends Controller
{
    public function index()
    {
        $warehouseId = request('warehouse');

        $query = DB::table('products')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')

            // ✅ FIX: warehouse-aware join
            ->leftJoin('stock_movements', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'stock_movements.product_id');

                if ($warehouseId) {
                    $join->where('stock_movements.warehouse_id', $warehouseId);
                }
            })

            ->select(
                'products.id',
                'products.sku',
                'products.name',
                'products.price',
                'products.min_stock',
                'suppliers.name as supplier_name',

                // ✅ STOCK (warehouse-aware)
                DB::raw("
                    COALESCE(SUM(
                        CASE
                            WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                            WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                            ELSE 0
                        END
                    ),0) as stock
                ")
            )

            ->groupBy(
                'products.id',
                'products.sku',
                'products.name',
                'products.price',
                'products.min_stock',
                'suppliers.name'
            );

        if (request('search')) {

            $search = trim(strtolower(request('search')));

            $terms = array_filter(explode(' ', $search));

            $query->where(function ($q) use ($terms) {

                foreach ($terms as $term) {

                    $q->where(function ($sub) use ($term) {
                        $sub->whereRaw('LOWER(products.name) LIKE ?', ['%' . $term . '%'])
                            ->orWhereRaw('LOWER(products.sku) LIKE ?', ['%' . $term . '%']);
                    });

                }

            });

        }

        /*
        SUPPLIER FILTER
        */
        if (request('supplier')) {
            $query->where('products.supplier_id', request('supplier'));
        }

        /*
        PRICE FILTER
        */
        if (request('min_price')) {
            $query->where('products.price', '>=', request('min_price'));
        }

        if (request('max_price')) {
            $query->where('products.price', '<=', request('max_price'));
        }

        /*
        STATUS FILTER
        */
        if (request('status') === 'low') {
            $query->havingRaw("
                COALESCE(SUM(
                    CASE
                        WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                        WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                        ELSE 0
                    END
                ),0) < products.min_stock
            ");
        }

        if (request('status') === 'out') {
            $query->havingRaw("
                COALESCE(SUM(
                    CASE
                        WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                        WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                        ELSE 0
                    END
                ),0) <= 0
            ");
        }

        $products = $query
            ->paginate(20)
            ->withQueryString();

        $suppliers = DB::table('suppliers')->get();
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

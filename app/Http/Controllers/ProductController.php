<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\InventoryForecastService;
class ProductController extends Controller
{
    public function index()
    {
        $query = DB::table('products')
            ->leftJoin('suppliers','products.supplier_id','=','suppliers.id')
            ->leftJoin('stock_movements','products.id','=','stock_movements.product_id')
            ->select(
                'products.id',
                'products.sku',
                'products.name',
                'products.price',
                'products.min_stock',
                'suppliers.name as supplier_name',
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
            ->groupBy('products.id','suppliers.name');


        /*
        SEARCH
        */

        if(request('search')) {

            $search = request('search');

            $query->where(function ($q) use ($search) {

                $q->where('products.name','like','%'.$search.'%')
                    ->orWhere('products.sku','like','%'.$search.'%');

            });

        }


        /*
        SUPPLIER FILTER
        */

        if(request('supplier')) {
            $query->where('products.supplier_id',request('supplier'));
        }


        /*
        PRICE FILTER
        */

        if(request('min_price')) {
            $query->where('products.price','>=',request('min_price'));
        }

        if(request('max_price')) {
            $query->where('products.price','<=',request('max_price'));
        }


        /*
        STATUS FILTER
        */

        if(request('status') === 'low') {
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

        if(request('status') === 'out') {
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


        return view('products.index', compact('products', 'suppliers'));
    }

    public function store(StoreProductRequest $request)
    {
        return Product::create($request->validated());
    }

    public function show(Product $product)
    {
        $movements = $product->stockMovements()
            ->orderBy('created_at')
            ->get();

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

        // newest first
        $movements = $movements->reverse()->take(50);

        // FORECAST
        $forecastService = new InventoryForecastService();

        $daysUntilOut = $forecastService->forecast($product);

        return view('products.show', [
            'product' => $product,
            'movements' => $movements,
            'stock' => $currentStock,
            'daysUntilOut' => $daysUntilOut
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

        $products = DB::table('products')

            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.price',

                DB::raw("
            (
                SELECT COALESCE(SUM(
                    CASE
                        WHEN type = 'in' THEN quantity
                        WHEN type = 'out' THEN -quantity
                    END
                ),0)
                FROM stock_movements
                WHERE stock_movements.product_id = products.id
            ) as stock
            "),

                DB::raw("
            (
                SELECT COALESCE(SUM(order_items.quantity),0)
                FROM order_items
                JOIN orders ON orders.id = order_items.order_id
                WHERE order_items.product_id = products.id
                AND orders.status IN ('draft','confirmed')
            ) as reserved
            ")
            )

            ->when($query, function ($q) use ($query) {

                $q->where(function($sub) use ($query){

                    $sub->where('products.name','like','%'.$query.'%')
                        ->orWhere('products.sku','like','%'.$query.'%');

                });

            })

            ->orderBy('products.name')
            ->get();

        $products->transform(function ($product) {

            $product->available = $product->stock - $product->reserved;

            return $product;

        });

        return response()->json($products);
    }
}

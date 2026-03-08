<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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

        // newest first for UI
        $movements = $movements->reverse()->take(50);

        return view('products.show', [
            'product' => $product,
            'movements' => $movements,
            'stock' => $currentStock
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

        $products = Product::query()
            ->when($query, function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($query)."%"])
                    ->orWhereRaw('LOWER(sku) LIKE ?', ["%".strtolower($query)."%"]);
            })
            ->orderBy('name')
            ->limit($query ? 20 : 10)
            ->get(['id','name','sku','price']);

        return response()->json($products);
    }
}

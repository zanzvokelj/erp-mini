<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class InventoryController extends Controller
{
    public function index()
    {
        $stockExpr = "
        COALESCE(SUM(
            CASE
                WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                ELSE 0
            END
        ),0)
    ";

        $query = DB::table('products')
            ->leftJoin('stock_movements','products.id','=','stock_movements.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.min_stock',
                DB::raw("$stockExpr as stock")
            )
            ->groupBy('products.id','products.name','products.min_stock');

        /*
        SEARCH
        */

        if(request('search')) {
            $query->where('products.name','ILIKE','%'.request('search').'%');
        }

        /*
        STATUS FILTER
        */

        if(request('status') === 'low') {
            $query->havingRaw("$stockExpr < products.min_stock AND $stockExpr > 0");
        }

        if(request('status') === 'out') {
            $query->havingRaw("$stockExpr <= 0");
        }

        if(request('status') === 'in') {
            $query->havingRaw("$stockExpr >= products.min_stock");
        }

        $inventory = $query
            ->orderBy('products.name')
            ->get();

        return view('inventory.index', compact('inventory'));
    }
}

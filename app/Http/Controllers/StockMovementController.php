<?php

namespace App\Http\Controllers;

use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function index()
    {
        $query = DB::table('stock_movements')
            ->where('stock_movements.company_id', app(CompanyContext::class)->id())
            ->join('products','stock_movements.product_id','=','products.id')
            ->leftJoin('warehouses','stock_movements.warehouse_id','=','warehouses.id')
            ->select(
                'stock_movements.id',
                'stock_movements.type',
                'stock_movements.quantity',
                'stock_movements.reference_id',
                'stock_movements.created_at',
                'products.name as product_name',
                'products.sku as product_sku',
                'warehouses.name as warehouse_name'
            );

        /*
        SEARCH PRODUCT
        */

        if(request('search')) {
            $query->where('products.name','ILIKE','%'.request('search').'%');
        }

        /*
        TYPE FILTER
        */

        if(request('type')) {
            $query->where('stock_movements.type', request('type'));
        }

        $movements = $query
            ->orderByDesc('stock_movements.created_at')
            ->paginate(20)
            ->withQueryString();

        return view('stock-movements.index', compact('movements'));
    }
}

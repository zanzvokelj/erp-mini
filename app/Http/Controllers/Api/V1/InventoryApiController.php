<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Resources\StockMovementResource;

class InventoryApiController extends Controller
{
    public function index()
    {
        $query = DB::table('products')
            ->leftJoin('stock_movements','products.id','=','stock_movements.product_id')
            ->select(
                'products.id',
                'products.sku',
                'products.name',
                'products.min_stock',
                DB::raw("
                    COALESCE(SUM(
                        CASE
                            WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                            WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                        END
                    ),0) as stock
                ")
            )
            ->groupBy('products.id');

        if(request('status') === 'low'){
            $query->havingRaw('stock < products.min_stock');
        }

        if(request('status') === 'out'){
            $query->havingRaw('stock <= 0');
        }

        $perPage = request('per_page',20);

        return $query->paginate($perPage);
    }

    public function adjust(Request $request)
    {
        $request->validate([
            'product_id' => ['required','exists:products,id'],
            'type' => ['required','in:in,out'],
            'quantity' => ['required','integer','min:1']
        ]);

        DB::table('stock_movements')->insert([
            'product_id' => $request->product_id,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'reference_type' => 'manual_adjustment',
            'reference_id' => null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return [
            'message' => 'Stock adjusted'
        ];
    }

    public function movements()
    {
        return StockMovementResource::collection(
            DB::table('stock_movements')
                ->latest()
                ->paginate(50)
        );
    }
}

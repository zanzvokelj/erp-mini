<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryQueryService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Resources\StockMovementResource;

class InventoryApiController extends Controller
{
    public function __construct(
        protected InventoryQueryService $inventoryQueryService,
        protected InventoryService $inventoryService
    ) {}

    public function index()
    {
        $query = $this->inventoryQueryService->overviewQuery();

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

        $product = Product::findOrFail($request->product_id);

        $this->inventoryService->adjustStock(
            $product,
            $request->type,
            (int) $request->quantity,
            null,
            auth()->id()
        );

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

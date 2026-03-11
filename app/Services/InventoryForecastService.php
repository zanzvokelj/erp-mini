<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class InventoryForecastService
{
    public function forecast(Product $product)
    {
        $salesLast30Days = DB::table('order_items')
            ->join('orders','orders.id','=','order_items.order_id')
            ->where('order_items.product_id',$product->id)
            ->where('orders.status','completed')
            ->where('orders.created_at','>=',now()->subDays(30))
            ->sum('order_items.quantity');

        $avgDailySales = $salesLast30Days / 30;

        if ($avgDailySales == 0) {
            return null;
        }

        $stock = DB::table('stock_movements')
            ->selectRaw("
                COALESCE(SUM(
                    CASE
                        WHEN type = 'in' THEN quantity
                        WHEN type = 'out' THEN -quantity
                    END
                ),0) as stock
            ")
            ->where('product_id',$product->id)
            ->value('stock');

        return round($stock / $avgDailySales);
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ReorderSuggestionService
{
    public function suggestions()
    {
        $products = Product::with('supplier')->get();

        $suggestions = [];

        foreach ($products as $product) {

            $sales = DB::table('order_items')
                ->join('orders','orders.id','=','order_items.order_id')
                ->where('order_items.product_id',$product->id)
                ->where('orders.status','completed')
                ->where('orders.created_at','>=',now()->subDays(30))
                ->sum('order_items.quantity');

            $avgDailySales = $sales / 30;

            if ($avgDailySales <= 0) {
                continue;
            }

            $stock = DB::table('stock_movements')
                ->selectRaw("
                    COALESCE(SUM(
                        CASE
                            WHEN type='in' THEN quantity
                            WHEN type='out' THEN -quantity
                        END
                    ),0) as stock
                ")
                ->where('product_id',$product->id)
                ->value('stock');

            $daysUntilOut = $stock / $avgDailySales;

            $leadTime = $product->supplier->lead_time_days ?? 7;

            if ($daysUntilOut <= $leadTime) {

                $suggestedQty =
                    ($avgDailySales * $leadTime * 2) - $stock;

                $suggestions[] = [
                    'product' => $product,
                    'stock' => round($stock),
                    'runout' => round($daysUntilOut),
                    'suggested_qty' => max(1, round($suggestedQty))
                ];
            }
        }

        return $suggestions;
    }
}

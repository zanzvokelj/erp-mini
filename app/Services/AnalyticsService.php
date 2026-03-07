<?php

namespace App\Services;
use Illuminate\Support\Facades\DB;
class AnalyticsService
{
    /**
     * Create a new class instance.
     */

    public function totalRevenue()
    {
        return DB::table('orders')
            ->where('status', 'confirmed')
            ->sum('total');
    }

    public function totalOrders()
    {
        return DB::table('orders')->count();
    }

    public function lowStockCount()
    {
        return $this->lowStockProducts()->count();
    }
    public function monthlyRevenue()
    {
        return DB::table('orders')
            ->selectRaw("DATE_TRUNC('month', created_at) as month, SUM(total) as revenue")
            ->where('status', 'confirmed')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    public function recentOrders()
    {
        return \App\Models\Order::with('customer')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function averageOrderValue()
    {
        return DB::table('orders')
            ->where('status', 'confirmed')
            ->avg('total');
    }

    public function topProducts()
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as sold')
            )
            ->groupBy('products.name')
            ->orderByDesc('sold')
            ->limit(5)
            ->get();
    }

    public function revenueGrowth()
    {
        $currentMonth = DB::table('orders')
            ->where('status','confirmed')
            ->whereMonth('created_at', now()->month)
            ->sum('total');

        $lastMonth = DB::table('orders')
            ->where('status','confirmed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum('total');

        if ($lastMonth == 0) {
            return 0;
        }

        return (($currentMonth - $lastMonth) / $lastMonth) * 100;
    }





    public function lowStockProducts()
    {
        return DB::table('products')
            ->leftJoin('stock_movements', 'products.id', '=', 'stock_movements.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.min_stock',
                DB::raw("
                COALESCE(SUM(
                    CASE
                        WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                        WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                        ELSE stock_movements.quantity
                    END
                ),0) as stock
            ")
            )
            ->groupBy('products.id', 'products.name', 'products.min_stock')
            ->havingRaw("
            COALESCE(SUM(
                CASE
                    WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                    WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                    ELSE stock_movements.quantity
                END
            ),0) < products.min_stock
        ")
            ->orderBy('stock')
            ->limit(10)
            ->get();
    }

    public function stockTurnover()
    {
        $cogs = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'confirmed')
            ->selectRaw('SUM(order_items.quantity * order_items.cost_at_time) as cogs')
            ->value('cogs');

        $inventory = DB::table('stock_movements')
            ->selectRaw("
            SUM(
                CASE
                    WHEN type = 'in' THEN quantity
                    WHEN type = 'out' THEN -quantity
                    ELSE quantity
                END
            ) as inventory
        ")
            ->value('inventory');

        if (!$inventory) {
            return 0;
        }

        return $cogs / $inventory;
    }





}

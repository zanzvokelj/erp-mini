<?php

namespace App\Services;

use App\Services\Concerns\ScopesCurrentCompany;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    use ScopesCurrentCompany;

    protected function remember(string $key, callable $callback)
    {
        return Cache::remember(
            'dashboard:' . $this->companyId() . ':' . $key,
            now()->addSeconds($this->cacheTtlSeconds()),
            $callback
        );
    }

    protected function cacheTtlSeconds(): int
    {
        return max((int) config('app.dashboard_cache_ttl', 120), 1);
    }

    public function totalRevenue()
    {
        return $this->remember('total_revenue', fn () => $this->scopeCompany(DB::table('orders'), 'orders')
            ->where('status', 'completed')
            ->sum('total'));
    }

    public function totalOrders()
    {
        return $this->remember('total_orders', fn () => $this->scopeCompany(DB::table('orders'), 'orders')
            ->whereIn('status', ['completed','shipped'])
            ->count());
    }

    public function lowStockCount()
    {
        return $this->lowStockProducts()->count();
    }
    public function monthlyRevenue()
    {
        return $this->remember('monthly_revenue', function () {
            $start = now()->copy()->startOfMonth()->subMonths(5);

            $raw = $this->scopeCompany(DB::table('orders'), 'orders')
                ->selectRaw("DATE_TRUNC('month', created_at) as month, SUM(total) as revenue")
                ->where('status', 'completed')
                ->where('created_at', '>=', $start)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->mapWithKeys(function ($row) {
                    $month = Carbon::parse($row->month)->format('Y-m');

                    return [$month => round((float) $row->revenue, 2)];
                });

            return collect(range(0, 5))
                ->map(function (int $offset) use ($start, $raw) {
                    $month = $start->copy()->addMonths($offset);
                    $key = $month->format('Y-m');

                    return [
                        'month' => $key,
                        'label' => $month->format('M Y'),
                        'revenue' => (float) ($raw[$key] ?? 0),
                    ];
                });
        });
    }

    public function recentOrders()
    {
        return $this->remember('recent_orders', fn () => \App\Models\Order::with('customer')
            ->where('company_id', $this->companyId())
            ->latest()
            ->limit(10)
            ->get());
    }

    public function averageOrderValue()
    {
        return $this->remember('average_order_value', fn () => $this->scopeCompany(DB::table('orders'), 'orders')
            ->where('status', 'completed')
            ->avg('total'));
    }

    public function topProducts()
    {
        return $this->remember('top_products', fn () => DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as sold')
            )
            ->where('orders.company_id', $this->companyId())
            ->where('products.company_id', $this->companyId())
            ->where('orders.status', 'completed')
            ->groupBy('products.name')
            ->orderByDesc('sold')
            ->limit(5)
            ->get());
    }

    public function revenueGrowth()
    {
        return $this->remember('revenue_growth', function () {
            $currentMonth = $this->scopeCompany(DB::table('orders'), 'orders')
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total');

            $lastMonthDate = now()->copy()->subMonth();

            $lastMonth = $this->scopeCompany(DB::table('orders'), 'orders')
                ->where('status', 'completed')
                ->whereMonth('created_at', $lastMonthDate->month)
                ->whereYear('created_at', $lastMonthDate->year)
                ->sum('total');

            if ($lastMonth == 0) {
                return 0;
            }

            return (($currentMonth - $lastMonth) / $lastMonth) * 100;
        });
    }






    public function lowStockProducts()
    {
        return $this->remember('low_stock_products', fn () => $this->scopeCompany(DB::table('products'), 'products')
            ->leftJoin('stock_movements', 'products.id', '=', 'stock_movements.product_id')
            ->select(
                'products.id',
                'products.name',
                'products.min_stock',
                DB::raw("
                COALESCE(SUM(
                    CASE
                        WHEN stock_movements.company_id = products.company_id AND stock_movements.type = 'in' THEN stock_movements.quantity
                        WHEN stock_movements.company_id = products.company_id AND stock_movements.type = 'out' THEN -stock_movements.quantity
                        ELSE 0
                    END
                ),0) as stock
            ")
            )
            ->groupBy('products.id', 'products.name', 'products.min_stock')
            ->havingRaw("
            COALESCE(SUM(
                CASE
                    WHEN stock_movements.company_id = products.company_id AND stock_movements.type = 'in' THEN stock_movements.quantity
                    WHEN stock_movements.company_id = products.company_id AND stock_movements.type = 'out' THEN -stock_movements.quantity
                    ELSE 0
                END
            ),0) < products.min_stock
        ")
            ->orderBy('stock')
            ->limit(10)
            ->get());
    }

    public function stockTurnover()
    {
        return $this->remember('stock_turnover', function () {
            $cogs = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.company_id', $this->companyId())
                ->where('orders.status', 'completed')
                ->selectRaw('SUM(order_items.quantity * order_items.cost_at_time) as cogs')
                ->value('cogs');

            $inventory = $this->scopeCompany(DB::table('stock_movements'), 'stock_movements')
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

            if (! $inventory) {
                return 0;
            }

            return $cogs / $inventory;
        });
    }

    public function inventoryValue()
    {
        return $this->remember('inventory_value', fn () => $this->scopeCompany(DB::table('products'), 'products')
            ->leftJoin('stock_movements','products.id','=','stock_movements.product_id')
            ->selectRaw("
            SUM(
                CASE
                    WHEN stock_movements.company_id = products.company_id AND stock_movements.type='in' THEN stock_movements.quantity
                    WHEN stock_movements.company_id = products.company_id AND stock_movements.type='out' THEN -stock_movements.quantity
                    ELSE 0
                END * products.cost_price
            ) as value
        ")
            ->value('value') ?? 0);
    }

    public function paidForInventory()
    {
        return $this->remember('paid_for_inventory', fn () => DB::table('purchase_order_items')
            ->join(
                'purchase_orders',
                'purchase_orders.id',
                '=',
                'purchase_order_items.purchase_order_id'
            )
            ->where('purchase_orders.company_id', $this->companyId())
            ->where('purchase_orders.status', 'received')
            ->selectRaw('SUM(purchase_order_items.quantity * purchase_order_items.cost_price) as total')
            ->value('total') ?? 0);
    }


    public function ordersToday()
    {
        return $this->remember('orders_today', fn () => \App\Models\Order::where('company_id', $this->companyId())
            ->whereDate('created_at', today())
            ->count());
    }

    public function revenueToday()
    {
        return $this->remember('revenue_today', fn () => \App\Models\Order::where('company_id', $this->companyId())
            ->whereDate('created_at', today())
            ->where('status','completed')
            ->sum('total'));
    }

    public function pendingOrders()
    {
        return $this->remember('pending_orders', fn () => \App\Models\Order::where('company_id', $this->companyId())
            ->where('status','confirmed')
            ->count());
    }

    public function topCustomers()
    {
        return $this->remember('top_customers', fn () => \DB::table('orders')
            ->join('customers','orders.customer_id','=','customers.id')
            ->select('customers.name', \DB::raw('SUM(orders.total) as revenue'))
            ->where('orders.company_id', $this->companyId())
            ->where('customers.company_id', $this->companyId())
            ->where('orders.status','completed')
            ->groupBy('customers.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get());
    }

    public function totalProfit()
    {
        return $this->remember('total_profit', fn () => \DB::table('order_items')
            ->join('orders','orders.id','=','order_items.order_id')
            ->where('orders.company_id', $this->companyId())
            ->where('orders.status','completed')
            ->selectRaw("
            SUM(
                (price_at_time - cost_at_time) * quantity
            ) as profit
        ")
            ->value('profit') ?? 0);
    }
}

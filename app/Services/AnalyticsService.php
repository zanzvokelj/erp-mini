<?php

namespace App\Services;

use App\Accounting\PostingMap;
use App\Services\Concerns\ScopesCurrentCompany;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    use ScopesCurrentCompany;

    public function __construct(
        protected ProfitAndLossService $profitAndLossService
    ) {
    }

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
        return $this->remember('total_revenue', fn () => $this->sumByAccountType('revenue'));
    }

    public function totalOrders()
    {
        return $this->remember('total_orders', fn () => $this->scopeCompany(DB::table('invoices'), 'invoices')
            ->where('status', '!=', 'cancelled')
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

            $raw = DB::table('journal_entries')
                ->join('journal_lines', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
                ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
                ->where('journal_entries.company_id', $this->companyId())
                ->where('accounts.company_id', $this->companyId())
                ->where('accounts.type', 'revenue')
                ->where('journal_entries.posted_at', '>=', $start)
                ->selectRaw("DATE_TRUNC('month', journal_entries.posted_at) as month, SUM(journal_lines.credit - journal_lines.debit) as revenue")
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
        return $this->remember('average_order_value', fn () => $this->scopeCompany(DB::table('invoices'), 'invoices')
            ->where('status', '!=', 'cancelled')
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
            $currentMonth = $this->sumByAccountType(
                'revenue',
                now()->copy()->startOfMonth(),
                now()->copy()->endOfMonth()
            );

            $lastMonthDate = now()->copy()->subMonth();

            $lastMonth = $this->sumByAccountType(
                'revenue',
                $lastMonthDate->copy()->startOfMonth(),
                $lastMonthDate->copy()->endOfMonth()
            );

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
            $cogs = $this->accountBalanceByCode(PostingMap::COST_OF_GOODS_SOLD);
            $inventory = $this->accountBalanceByCode(PostingMap::INVENTORY_ASSET);

            if ($inventory <= 0) {
                return 0;
            }

            return round($cogs / $inventory, 4);
        });
    }

    public function inventoryValue()
    {
        return $this->remember('inventory_value', fn () => $this->accountBalanceByCode(PostingMap::INVENTORY_ASSET));
    }

    public function paidForInventory()
    {
        return $this->remember('paid_for_inventory', fn () => (float) ($this->scopeCompany(DB::table('supplier_payments'), 'supplier_payments')
            ->sum('amount') ?? 0));
    }


    public function ordersToday()
    {
        return $this->remember('orders_today', fn () => \App\Models\Order::where('company_id', $this->companyId())
            ->whereDate('created_at', today())
            ->count());
    }

    public function revenueToday()
    {
        return $this->remember('revenue_today', fn () => $this->sumByAccountType(
            'revenue',
            now()->copy()->startOfDay(),
            now()->copy()->endOfDay()
        ));
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
        return $this->remember('total_profit', fn () => (float) $this->profitAndLossService->build()['summary']['net_profit']);
    }

    protected function sumByAccountType(
        string $type,
        ?Carbon $dateFrom = null,
        ?Carbon $dateTo = null
    ): float {
        $rows = DB::table('accounts')
            ->leftJoin('journal_lines', 'accounts.id', '=', 'journal_lines.account_id')
            ->leftJoin('journal_entries', function ($join) use ($dateFrom, $dateTo) {
                $join->on('journal_entries.id', '=', 'journal_lines.journal_entry_id');
                $join->whereColumn('journal_entries.company_id', 'accounts.company_id');

                if ($dateFrom) {
                    $join->where('journal_entries.posted_at', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $join->where('journal_entries.posted_at', '<=', $dateTo);
                }
            })
            ->where('accounts.company_id', $this->companyId())
            ->where('accounts.type', $type)
            ->selectRaw('accounts.type, COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.debit ELSE 0 END), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.credit ELSE 0 END), 0) as total_credit')
            ->groupBy('accounts.id', 'accounts.type')
            ->get();

        return round((float) $rows->sum(function ($row) {
            return match ($row->type) {
                'asset', 'expense' => (float) $row->total_debit - (float) $row->total_credit,
                default => (float) $row->total_credit - (float) $row->total_debit,
            };
        }), 2);
    }

    protected function accountBalanceByCode(string $code): float
    {
        $row = DB::table('accounts')
            ->leftJoin('journal_lines', 'accounts.id', '=', 'journal_lines.account_id')
            ->leftJoin('journal_entries', function ($join) {
                $join->on('journal_entries.id', '=', 'journal_lines.journal_entry_id');
                $join->whereColumn('journal_entries.company_id', 'accounts.company_id');
            })
            ->where('accounts.company_id', $this->companyId())
            ->where('accounts.code', $code)
            ->selectRaw('accounts.type, COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.debit ELSE 0 END), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(CASE WHEN journal_entries.id IS NOT NULL THEN journal_lines.credit ELSE 0 END), 0) as total_credit')
            ->groupBy('accounts.id', 'accounts.type')
            ->first();

        if (! $row) {
            return 0;
        }

        return round(match ($row->type) {
            'asset', 'expense' => (float) $row->total_debit - (float) $row->total_credit,
            default => (float) $row->total_credit - (float) $row->total_debit,
        }, 2);
    }
}

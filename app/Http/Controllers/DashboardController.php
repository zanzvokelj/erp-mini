<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;

class DashboardController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    /**
     * Dashboard page
     */
    public function dashboard()
    {
        $lowStockProducts = $this->analytics->lowStockProducts();

        return view('dashboard', [

            'revenue' => $this->analytics->totalRevenue(),

            'ordersCount' => $this->analytics->totalOrders(),

            'avgOrderValue' => $this->analytics->averageOrderValue(),

            'lowStockProducts' => $lowStockProducts,

            'lowStockCount' => $lowStockProducts->count(),

            'recentOrders' => $this->analytics->recentOrders(),

            'topProducts' => $this->analytics->topProducts(),

            'stockTurnover' => $this->analytics->stockTurnover(),

            'revenueGrowth' => $this->analytics->revenueGrowth(),

            'inventoryValue' => $this->analytics->inventoryValue(),

            'paidForInventory' => $this->analytics->paidForInventory(),

            'ordersToday' => $this->analytics->ordersToday(),

            'revenueToday' => $this->analytics->revenueToday(),

            'pendingOrders' => $this->analytics->pendingOrders(),

            'topCustomers' => $this->analytics->topCustomers(),

            'totalProfit' => $this->analytics->totalProfit(),

            'monthlyRevenue' => $this->analytics->monthlyRevenue(),
        ]);
    }

    /**
     * Analytics API
     */
    public function index()
    {
        return response()->json([
            'monthly_revenue' => $this->analytics->monthlyRevenue(),
            'average_order_value' => $this->analytics->averageOrderValue(),
            'top_products' => $this->analytics->topProducts(),
            'low_stock_products' => $this->analytics->lowStockProducts(),
            'stock_turnover' => $this->analytics->stockTurnover(),
        ]);
    }


}

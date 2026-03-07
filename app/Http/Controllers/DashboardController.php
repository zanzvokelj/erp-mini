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
        return view('dashboard', [

            'revenue' => $this->analytics->totalRevenue(),

            'ordersCount' => $this->analytics->totalOrders(),

            'avgOrderValue' => $this->analytics->averageOrderValue(),

            'lowStockProducts' => $this->analytics->lowStockProducts(),

            'lowStockCount' => $this->analytics->lowStockProducts()->count(),

            'recentOrders' => $this->analytics->recentOrders(),

            'topProducts' => $this->analytics->topProducts(),

            'stockTurnover' => $this->analytics->stockTurnover(),

            'revenueGrowth' => $this->analytics->revenueGrowth(),
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

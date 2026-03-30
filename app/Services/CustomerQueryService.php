<?php

namespace App\Services;

use App\Services\Concerns\ScopesCurrentCompany;
use Illuminate\Support\Facades\DB;

class CustomerQueryService
{
    use ScopesCurrentCompany;

    public function getCustomers(array $filters)
    {
        $query = $this->scopeCompany(DB::table('customers'), 'customers');

        // SEARCH
        if (!empty($filters['search'])) {
            $query->where('name', 'ILIKE', '%' . $filters['search'] . '%');
        }

        // TYPE
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }

    public function getCustomerWithStats(int $id)
    {
        $customer = $this->scopeCompany(DB::table('customers'), 'customers')
            ->where('id', $id)
            ->first();

        $orders = $this->scopeCompany(DB::table('orders'), 'orders')
            ->where('customer_id', $id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $stats = $this->scopeCompany(DB::table('orders'), 'orders')
            ->where('customer_id', $id)
            ->selectRaw("
                COUNT(*) as total_orders,
                SUM(total) as total_revenue,
                AVG(total) as avg_order
            ")
            ->first();

        return compact('customer', 'orders', 'stats');
    }
}

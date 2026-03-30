<?php

namespace App\Services;

use App\Services\Concerns\ScopesCurrentCompany;
use Illuminate\Support\Facades\DB;

class ProductQueryService
{
    use ScopesCurrentCompany;

    public function getProducts(array $filters)
    {
        $warehouseId = $filters['warehouse'] ?? null;

        $query = $this->scopeCompany(DB::table('products'), 'products')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->leftJoin('stock_movements', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'stock_movements.product_id');

                if ($warehouseId) {
                    $join->where('stock_movements.warehouse_id', $warehouseId);
                }

                $join->where('stock_movements.company_id', $this->companyId());
            })
            ->select(
                'products.id',
                'products.sku',
                'products.name',
                'products.price',
                'products.min_stock',
                'suppliers.name as supplier_name',
                DB::raw("
                    COALESCE(SUM(
                        CASE
                            WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                            WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                            ELSE 0
                        END
                    ),0) as stock
                ")
            )
            ->groupBy(
                'products.id',
                'products.sku',
                'products.name',
                'products.price',
                'products.min_stock',
                'suppliers.name'
            );

        // SEARCH
        if (!empty($filters['search'])) {
            $search = trim(strtolower($filters['search']));
            $terms = array_filter(explode(' ', $search));

            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($sub) use ($term) {
                        $sub->whereRaw('LOWER(products.name) LIKE ?', ['%' . $term . '%'])
                            ->orWhereRaw('LOWER(products.sku) LIKE ?', ['%' . $term . '%']);
                    });
                }
            });
        }

        // SUPPLIER
        if (!empty($filters['supplier'])) {
            $query->where('products.supplier_id', $filters['supplier']);
        }

        // PRICE
        if (!empty($filters['min_price'])) {
            $query->where('products.price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('products.price', '<=', $filters['max_price']);
        }

        // STATUS
        if (($filters['status'] ?? null) === 'low') {
            $query->havingRaw("
                COALESCE(SUM(
                    CASE
                        WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                        WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                        ELSE 0
                    END
                ),0) < products.min_stock
            ");
        }

        if (($filters['status'] ?? null) === 'out') {
            $query->havingRaw("
                COALESCE(SUM(
                    CASE
                        WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                        WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                        ELSE 0
                    END
                ),0) <= 0
            ");
        }

        return $query->paginate(20)->withQueryString();
    }
}

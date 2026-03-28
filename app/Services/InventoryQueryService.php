<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InventoryQueryService
{
    public function overviewQuery(?int $warehouseId = null)
    {
        $stockExpr = "
            COALESCE(SUM(
                CASE
                    WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                    WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                    ELSE 0
                END
            ),0)
        ";

        return DB::table('products')
            ->leftJoin('stock_movements', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'stock_movements.product_id');

                if ($warehouseId) {
                    $join->where('stock_movements.warehouse_id', $warehouseId);
                }
            })
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.min_stock',
                DB::raw("$stockExpr as stock")
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.min_stock');
    }

    public function getInventory(array $filters)
    {
        $warehouseId = $filters['warehouse'] ?? null;
        $stockExpr = "
            COALESCE(SUM(
                CASE
                    WHEN stock_movements.type = 'in' THEN stock_movements.quantity
                    WHEN stock_movements.type = 'out' THEN -stock_movements.quantity
                    ELSE 0
                END
            ),0)
        ";

        $query = $this->overviewQuery($warehouseId);

        // SEARCH
        if (!empty($filters['search'])) {
            $query->where('products.name', 'ILIKE', '%' . $filters['search'] . '%');
        }

        // STATUS
        if (($filters['status'] ?? null) === 'low') {
            $query->havingRaw("$stockExpr < products.min_stock AND $stockExpr > 0");
        }

        if (($filters['status'] ?? null) === 'out') {
            $query->havingRaw("$stockExpr <= 0");
        }

        if (($filters['status'] ?? null) === 'in') {
            $query->havingRaw("$stockExpr >= products.min_stock");
        }

        return $query
            ->orderBy('products.name')
            ->paginate(20)
            ->withQueryString();
    }
}

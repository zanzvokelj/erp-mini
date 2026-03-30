<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\CompanyContext;
use App\Services\InventoryQueryService;

class InventoryController extends Controller
{
    public function index(InventoryQueryService $inventoryQuery)
    {
        $warehouses = Warehouse::query()
            ->where('company_id', app(CompanyContext::class)->id())
            ->orderBy('name')
            ->get();

        $inventory = $inventoryQuery->getInventory(request()->all());

        return view('inventory.index', compact('inventory', 'warehouses'));
    }
}

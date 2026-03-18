<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\InventoryQueryService;

class InventoryController extends Controller
{
    public function index(InventoryQueryService $inventoryQuery)
    {
        $warehouses = Warehouse::orderBy('name')->get();

        $inventory = $inventoryQuery->getInventory(request()->all());

        return view('inventory.index', compact('inventory', 'warehouses'));
    }
}

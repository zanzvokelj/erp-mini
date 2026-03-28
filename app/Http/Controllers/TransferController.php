<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\TransferService;

class TransferController extends Controller
{
    public function index()
    {
        $transfers = WarehouseTransfer::with([
            'product',
            'fromWarehouse',
            'toWarehouse'
        ])
            ->latest()
            ->get();

        return view('transfers.index', compact('transfers'));
    }

    public function create()
    {
        return view('transfers.create', [
            'warehouses' => Warehouse::orderBy('name')->get()
        ]);
    }

    public function store(Request $request, TransferService $service)
    {
        // ✅ VALIDATION
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'from_warehouse' => ['required', 'exists:warehouses,id'],
            'to_warehouse' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {

            $service->transfer(
                Product::findOrFail($validated['product_id']),
                (int) $validated['from_warehouse'],
                (int) $validated['to_warehouse'],
                (int) $validated['quantity']
            );

            return redirect()
                ->route('transfers.index')
                ->with('success', 'Transfer completed successfully');

        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
}

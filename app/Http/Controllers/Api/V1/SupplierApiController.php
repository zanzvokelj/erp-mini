<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;

class SupplierApiController extends Controller
{
    public function index()
    {
        $query = Supplier::query();

        if (request('search')) {
            $query->where('name','like','%'.request('search').'%');
        }

        $perPage = request('per_page',20);

        return SupplierResource::collection(
            $query->orderBy('name')->paginate($perPage)
        );
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('products');

        return new SupplierResource($supplier);
    }
}

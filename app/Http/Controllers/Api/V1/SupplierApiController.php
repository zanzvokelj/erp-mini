<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\CompanyContext;

class SupplierApiController extends Controller
{
    public function index()
    {
        $query = Supplier::query()
            ->where('company_id', app(CompanyContext::class)->id());

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
        abort_if(
            (int) $supplier->company_id !== app(CompanyContext::class)->id(),
            404
        );

        $supplier->load('products');

        return new SupplierResource($supplier);
    }
}

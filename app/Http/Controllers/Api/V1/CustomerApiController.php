<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CompanyContext;

class CustomerApiController extends Controller
{
    public function index()
    {
        $query = Customer::query()
            ->where('company_id', app(CompanyContext::class)->id());

        if (request('search')) {
            $query->where('name','like','%'.request('search').'%');
        }

        $perPage = request('per_page',20);

        return CustomerResource::collection(
            $query->orderBy('name')->paginate($perPage)
        );
    }

    public function show(Customer $customer)
    {
        abort_if(
            (int) $customer->company_id !== app(CompanyContext::class)->id(),
            404
        );

        return new CustomerResource($customer);
    }
}

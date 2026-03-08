<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;

class CustomerApiController extends Controller
{
    public function index()
    {
        $query = Customer::query();

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
        return new CustomerResource($customer);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\CustomerQueryService;

class CustomerController extends Controller
{
    public function index(CustomerQueryService $customerQuery)
    {
        $customers = $customerQuery->getCustomers(request()->all());

        return view('customers.index', compact('customers'));
    }

    public function show($id, CustomerQueryService $customerQuery)
    {
        $data = $customerQuery->getCustomerWithStats($id);

        return view('customers.show', $data);
    }
}

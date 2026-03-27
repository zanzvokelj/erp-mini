<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
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

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:retail,wholesale'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
        ]);

        $customer = Customer::create($validated);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }
}

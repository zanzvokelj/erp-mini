<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Services\CustomerQueryService;
use App\Services\CompanyContext;

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

    public function search(Request $request)
    {
        $query = trim((string) $request->input('q', ''));
        $companyId = app(CompanyContext::class)->id();

        return Customer::query()
            ->where('company_id', $companyId)
            ->select('id', 'name', 'type')
            ->when($query !== '', function ($builder) use ($query) {
                $normalizedQuery = mb_strtolower($query);

                $builder->whereRaw('LOWER(name) LIKE ?', ["%{$normalizedQuery}%"]);
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'type' => $customer->type,
            ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:retail,wholesale'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
        ]);

        $customer = Customer::create($validated + [
            'company_id' => app(CompanyContext::class)->id(),
        ]);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }
}

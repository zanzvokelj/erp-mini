<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-gray-800">
            Customers
        </h2>
    </x-slot>

    <div class="space-y-6">

        <!-- Page header -->

        <div class="flex items-center justify-between">

            <h1 class="text-lg font-semibold text-gray-800">
                Customers
            </h1>

            <a
                href="{{ route('customers.create') }}"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
                Add Customer
            </a>

        </div>


        <!-- Filters -->

        <div class="bg-white border border-gray-200 rounded-lg p-4">

            <form method="GET" action="{{ route('customers.index') }}">

                <div class="flex items-center gap-4">

                    <input
                        name="search"
                        value="{{ request('search') }}"
                        type="text"
                        placeholder="Search customers..."
                        class="w-80 border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                    />

                    <select
                        name="type"
                        class="border border-gray-200 rounded-md px-4 py-2 text-sm"
                    >

                        <option value="">All Types</option>

                        <option value="wholesale" {{ request('type') == 'wholesale' ? 'selected' : '' }}>
                            Wholesale
                        </option>

                        <option value="retail" {{ request('type') == 'retail' ? 'selected' : '' }}>
                            Retail
                        </option>

                    </select>

                    <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md">
                        Filter
                    </button>

                    <a
                        href="{{ route('customers.index') }}"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-md bg-white hover:bg-gray-50"
                    >
                        Clear
                    </a>

                </div>

            </form>

        </div>


        <!-- Customers table -->

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">

                <tr>
                    <th class="text-left px-6 py-3">Name</th>
                    <th class="text-left px-6 py-3">Type</th>
                    <th class="text-left px-6 py-3">Discount</th>
                    <th class="text-left px-6 py-3">Credit Limit</th>
                    <th class="text-left px-6 py-3"></th>
                </tr>

                </thead>

                <tbody class="divide-y">

                @forelse($customers as $customer)

                    <tr>

                        <td class="px-6 py-3 font-medium">
                            {{ $customer->name }}
                        </td>

                        <td class="px-6 py-3">

<span class="px-2 py-1 text-xs rounded
{{ $customer->type === 'wholesale'
? 'bg-blue-100 text-blue-700'
: 'bg-gray-200 text-gray-700' }}">

{{ ucfirst($customer->type) }}

</span>

                        </td>

                        <td class="px-6 py-3">
                            {{ $customer->discount_percent }}%
                        </td>

                        <td class="px-6 py-3">
                            €{{ number_format($customer->credit_limit,2) }}
                        </td>

                        <td class="px-6 py-3">

                            <a href="{{ route('customers.show', $customer->id) }}"
                               class="px-3 py-1 text-xs border border-gray-200 rounded hover:bg-gray-50">
                                View
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                            No customers found
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        <!-- Pagination -->

        <div class="mt-4">
            {{ $customers->links() }}
        </div>

    </div>

</x-app-layout>

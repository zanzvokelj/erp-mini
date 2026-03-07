<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-gray-800">
            Suppliers
        </h2>
    </x-slot>

    <div class="space-y-6">

        <!-- Page header -->

        <div class="flex items-center justify-between">

            <h1 class="text-lg font-semibold text-gray-800">
                Suppliers
            </h1>

            <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Add Supplier
            </button>

        </div>


        <!-- Search -->

        <div class="bg-white border border-gray-200 rounded-lg p-4">

            <form method="GET" action="{{ route('suppliers.index') }}">

                <div class="flex items-center gap-4">

                    <input
                        name="search"
                        value="{{ request('search') }}"
                        type="text"
                        placeholder="Search suppliers..."
                        class="w-80 border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                    />

                    <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md">
                        Search
                    </button>

                    <a
                        href="{{ route('suppliers.index') }}"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-md bg-white hover:bg-gray-50"
                    >
                        Clear
                    </a>

                </div>

            </form>

        </div>


        <!-- Suppliers table -->

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">

                <tr>
                    <th class="text-left px-6 py-3">Name</th>
                    <th class="text-left px-6 py-3">Email</th>
                    <th class="text-left px-6 py-3">Phone</th>
                    <th class="text-left px-6 py-3">Lead Time</th>
                    <th class="text-left px-6 py-3"></th>
                </tr>

                </thead>

                <tbody class="divide-y">

                @forelse($suppliers as $supplier)

                    <tr>

                        <td class="px-6 py-3 font-medium">
                            {{ $supplier->name }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $supplier->email }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $supplier->phone }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $supplier->lead_time_days }} days
                        </td>

                        <td class="px-6 py-3">

                            <button class="px-3 py-1 text-xs border border-gray-200 rounded hover:bg-gray-50">
                                View
                            </button>

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                            No suppliers found
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        <!-- Pagination -->

        <div class="mt-4">
            {{ $suppliers->links() }}
        </div>

    </div>

</x-app-layout>

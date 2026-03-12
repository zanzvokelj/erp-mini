<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-gray-800">
            Stock Movements
        </h2>
    </x-slot>

    <div class="space-y-6">

        <h1 class="text-lg font-semibold text-gray-800">
            Stock Movements
        </h1>


        <!-- Filters -->

        <div class="bg-white border border-gray-200 rounded-lg p-4">

            <form method="GET" action="{{ route('stock-movements.index') }}">

                <div class="flex items-center gap-4">

                    <input
                        name="search"
                        value="{{ request('search') }}"
                        type="text"
                        placeholder="Search product..."
                        class="w-80 border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                    />

                    <select name="type" class="border border-gray-200 rounded-md mx-2 py-2 text-sm">

                        <option value="">All Types</option>

                        <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>
                            IN
                        </option>

                        <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>
                            OUT
                        </option>

                    </select>

                    <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md">
                        Filter
                    </button>

                    <a
                        href="{{ route('stock-movements.index') }}"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-md bg-white hover:bg-gray-50"
                    >
                        Clear
                    </a>

                </div>

            </form>

        </div>


        <!-- Table -->

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">

                <tr>
                    <th class="text-left px-6 py-3">Date</th>
                    <th class="text-left px-6 py-3">Warehouse</th>
                    <th class="text-left px-6 py-3">Product</th>
                    <th class="text-left px-6 py-3">Type</th>
                    <th class="text-left px-6 py-3">Quantity</th>
                    <th class="text-left px-6 py-3">Reference</th>
                </tr>

                </thead>

                <tbody class="divide-y">

                @forelse($movements as $movement)

                    <tr>

                        <td class="px-6 py-3">
                            {{ $movement->created_at }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $movement->warehouse_name ?? '-' }}
                        </td>

                        <td class="px-6 py-3">
                            <div class="flex flex-col">
                            <span>{{ $movement->product_name }}</span>
                                <span class="text-xs text-gray-500">SKU: {{ $movement->product_sku }}</span>
                            </div>
                        </td>




                        <td class="px-6 py-3">

<span class="px-2 py-1 text-xs rounded
{{ $movement->type === 'in'
? 'bg-blue-100 text-blue-700'
: 'bg-red-100 text-red-700' }}">

{{ strtoupper($movement->type) }}

</span>

                        </td>

                        <td class="px-6 py-3">
                            {{ $movement->quantity }}
                        </td>

                        <td class="px-6 py-3 text-gray-500">
                            {{ $movement->reference_id }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                            No movements found
                        </td>
                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        <!-- Pagination -->

        <div class="mt-4">
            {{ $movements->links() }}
        </div>

    </div>

</x-app-layout>

<x-app-layout>
    @php

        $statusClasses = [
        'in' => 'bg-green-100 text-green-700',
        'low' => 'bg-orange-100 text-orange-700',
        'out' => 'bg-red-100 text-red-700',
        ];

    @endphp


    <div class="space-y-6">

        <h1 class="text-lg font-semibold text-gray-800">
            Inventory
        </h1>

        <div class="bg-white border border-gray-200 rounded-lg p-4">

            <form method="GET" action="{{ route('inventory.index') }}">

                <div class="flex items-center gap-4">

                    <input
                        name="search"
                        value="{{ request('search') }}"
                        type="text"
                        placeholder="Search product..."
                        class="w-80 border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                    />

                    <select name="status" class="border border-gray-200 rounded-md px-8 py-2 text-sm">

                        <option value="">All Statuses</option>

                        <option value="in" {{ request('status') == 'in' ? 'selected' : '' }}>
                            In Stock
                        </option>

                        <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>
                            Low Stock
                        </option>

                        <option value="out" {{ request('status') == 'out' ? 'selected' : '' }}>
                            Out of Stock
                        </option>

                    </select>

                    <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md">
                        Filter
                    </button>

                    <a
                        href="{{ route('inventory.index') }}"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-md bg-white hover:bg-gray-50"
                    >
                        Clear
                    </a>

                </div>

            </form>

        </div>


        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">

                <tr>
                    <th class="text-left px-6 py-3">Product</th>
                    <th class="text-left px-6 py-3">Current Stock</th>
                    <th class="text-left px-6 py-3">Min Stock</th>
                    <th class="text-left px-6 py-3">Status</th>
                </tr>

                </thead>

                <tbody class="divide-y">

                @foreach($inventory as $product)

                    <tr>

                        <td class="px-6 py-3 font-medium">
                            <div class="flex flex-col">
                            <span>{{ $product->name }}</span>
                                <span class="text-xs text-gray-500">SKU: {{ $product->sku }}</span>
                            </div>
                        </td>

                        <td class="px-6 py-3">
                            {{ $product->stock }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $product->min_stock }}
                        </td>

                        <td class="px-6 py-3">

                            @php

                                $status = match(true) {
                                $product->stock <= 0 => 'out',
                                $product->stock < $product->min_stock => 'low',
                                default => 'in',
                                };

                            @endphp

                            <span class="px-2 py-1 text-xs rounded {{ $statusClasses[$status] }}">

@if($status === 'in')
                                    In Stock
                                @elseif($status === 'low')
                                    Low Stock
                                @else
                                    Out of Stock
                                @endif

</span>

                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

    </div>

</x-app-layout>

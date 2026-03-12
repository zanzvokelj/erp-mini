
<x-app-layout>

    @php

        $stockBadgeClasses = [
            'in' => 'bg-green-100 text-green-700',
            'low' => 'bg-orange-100 text-orange-700',
            'out' => 'bg-red-100 text-red-700',
        ];

    @endphp

<x-slot name="header">
    <h2 class="font-semibold text-lg text-gray-800">
Products
    </h2>
</x-slot>

<div class="space-y-6">

    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">

        <h1 class="text-lg font-semibold text-gray-800">
Products
        </h1>

        <div class="flex items-center gap-3">



            <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
Add Product
</button>

        </div>

    </div>

    <!-- Search filters -->
    <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">

        <form method="GET" action="{{ route('products.index') }}">

            <div class="flex items-center gap-4">

                <div x-data="{ search: '{{ request('search') }}' }">

                    <input
                        name="search"
                        x-model="search"
                        x-on:input.debounce.2000ms="$el.form.submit()"
                        type="text"
                        placeholder="Search by name or SKU..."
                        class="w-96 border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                    >

                </div>

                <select name="warehouse">
                    <option value="">All Warehouses</option>

                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                            {{ request('warehouse') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>

                <select name="supplier">
                    <option value="">All Suppliers</option>

                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}"
                            {{ request('supplier') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach

                </select>

                <select name="status">

                    <option value="">All Statuses</option>

                    <option value="in">In Stock</option>
                    <option value="low">Low Stock</option>
                    <option value="out">Out of Stock</option>

                </select>
                <button class="px-3 py-2 bg-gray-100 rounded">
                    Filter
                </button>

                <a
                    href="{{ route('products.index') }}"
                    class="px-3 py-2 text-sm border border-gray-200 rounded-md bg-white hover:bg-gray-50"
                >
                    Clear
                </a>

            </div>



        </form>
    </div>

    <!-- Products table -->
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">SKU</th>
                    <th class="text-left px-6 py-3">Product</th>
                    <th class="text-left px-6 py-3">Price</th>
                    <th class="text-left px-6 py-3">Stock</th>
                    <th class="text-left px-6 py-3">Min</th>
                    <th class="text-left px-6 py-3">Supplier</th>
                    <th class="text-left px-6 py-3">Status</th>
                </tr>
            </thead>

            <tbody class="divide-y">

            @foreach($products as $product)

                <tr>

                    <td class="px-6 py-3 font-medium">
                        {{ $product->sku }}
                    </td>

                    <td class="px-6 py-3">
                        <a
                            href="{{ route('products.show',$product->id) }}"
                            class="hover:bg-blue-100 px-2 py-2 rounded-xl"
                        >
                            {{ $product->name }}
                        </a>
                    </td>

                    <td class="px-6 py-3">
                        €{{ number_format($product->price,2) }}
                    </td>

                    <td class="px-6 py-3">
                        {{ $product->stock }}
                    </td>

                    <td class="px-6 py-3">
                        {{ $product->min_stock }}
                    </td>

                    <td class="px-6 py-3 text-blue-600">
                        {{ $product->supplier_name }}
                    </td>

                    <td class="px-6 py-3">

                        @php

                            $status = match(true) {
    $product->stock <= 0 => 'out',
    $product->stock < $product->min_stock => 'low',
    default => 'in',
};

                        @endphp

                        <span class="px-2 py-1 text-xs rounded {{ $stockBadgeClasses[$status] }}">

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

    <!-- Pagination -->
    <div class="mt-4">

        {{ $products->links() }}

    </div>

</div>

    <script src="//unpkg.com/alpinejs" defer></script>

</x-app-layout>

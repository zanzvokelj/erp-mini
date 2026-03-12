<x-app-layout>

    <div class="space-y-6">

        <!-- Header -->

        <div class="flex justify-between items-center">

            <div>
                <h1 class="text-lg font-semibold text-gray-800">
                    {{ $product->name }}
                </h1>

                <p class="text-sm text-gray-500">
                    SKU: {{ $product->sku }}
                </p>
            </div>

            <div class="text-right">

                <p class="text-sm text-gray-500">Current Stock</p>

                <div class="text-right">

                    <p class="text-sm text-gray-500">Total Stock</p>

                    <p class="text-2xl font-bold text-gray-800 mb-2">
                        {{ $stock }}
                    </p>

                    <div class="text-xs text-gray-600 space-y-1">

                        @foreach($warehouses as $warehouse)

                            <div class="flex justify-between gap-4">

                                <span>{{ $warehouse->name }}</span>

                                <span class="font-medium">
                    {{ $warehouseStock[$warehouse->id] ?? 0 }}
                </span>

                            </div>

                        @endforeach

                    </div>

                </div>

            </div>

        </div>


        @if($daysUntilOut)

            <div class="bg-yellow-50 border border-yellow-200 p-4 rounded">

                <div class="text-sm text-yellow-800">

                    ⚠ Stock forecast

                    <div class="mt-1 font-medium">
                        Inventory will run out in
                        <strong>{{ $daysUntilOut }} days</strong>
                    </div>

                </div>

            </div>

        @endif


        <!-- Product Info -->

        <div class="bg-white border border-gray-200 rounded-lg p-6">

            <div class="grid grid-cols-4 gap-6 text-sm">

                <div>
                    <p class="text-gray-500">Price</p>
                    <p class="font-medium">€{{ number_format($product->price,2) }}</p>
                </div>

                <div>
                    <p class="text-gray-500">Cost</p>
                    <p class="font-medium">€{{ number_format($product->cost_price,2) }}</p>
                </div>

                <div>
                    <p class="text-gray-500">Min Stock</p>
                    <p class="font-medium">{{ $product->min_stock }}</p>
                </div>

                <div>
                    <p class="text-gray-500">Supplier</p>
                    <p class="font-medium">{{ $product->supplier->name ?? '-' }}</p>
                </div>

            </div>

        </div>


        <!-- Stock History -->

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

            <div class="p-6 border-b">
                <h2 class="text-sm font-semibold text-gray-700">
                    Stock History
                </h2>
            </div>

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">

                <tr>
                    <th class="text-left px-6 py-3">Type</th>
                    <th class="text-left px-6 py-3">Quantity</th>
                    <th class="text-left px-6 py-3">Balance</th>
                    <th class="text-left px-6 py-3">Reference</th>
                    <th class="text-left px-6 py-3">Date</th>
                    <th class="text-left px-6 py-3">Warehouse</th>
                </tr>

                </thead>

                <tbody class="divide-y">

                @foreach($movements as $move)

                    <tr>

                        <td class="px-6 py-3">

                            @if($move->type === 'in')
                                <span class="text-green-600 font-medium">IN</span>
                            @else
                                <span class="text-red-600 font-medium">OUT</span>
                            @endif

                        </td>

                        <td class="px-6 py-3">

                            @if($move->type === 'in')
                                <span class="text-green-600">+{{ $move->quantity }}</span>
                            @else
                                <span class="text-red-600">-{{ $move->quantity }}</span>
                            @endif

                        </td>

                        <td class="px-6 py-3 font-medium">
                            {{ $move->balance }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $move->reference_type }}

                            @if($move->reference_id)
                                #{{ $move->reference_id }}
                            @endif

                        </td>

                        <td class="px-6 py-3 text-gray-500">
                            {{ $move->created_at->format('M j, Y') }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $move->warehouse->name ?? '-' }}
                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

    </div>

</x-app-layout>

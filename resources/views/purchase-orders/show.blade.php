<x-app-layout>

    <div class="space-y-6">

        <!-- PO HEADER -->

        <h1 class="text-lg font-semibold">
            {{ $po->po_number }}
        </h1>


        <!-- SUPPLIER -->

        <div class="bg-white border rounded p-6">

            <div class="grid grid-cols-2 gap-6">

                <div>
                    <div class="text-sm text-gray-500 mb-1">
                        Supplier
                    </div>

                    <div class="font-medium">
                        {{ $po->supplier->name }}
                    </div>
                </div>

                <div>
                    <div class="text-sm text-gray-500 mb-1">
                        Warehouse
                    </div>

                    <div class="font-medium">
                        {{ $po->warehouse->name ?? '-' }}
                    </div>
                </div>

            </div>

        </div>



        <!-- ITEMS -->

        <div class="bg-white border rounded p-6">

            <h2 class="font-semibold mb-4">
                Items
            </h2>

            <table class="w-full text-sm">

                <thead class="text-gray-500">

                <tr>
                    <th class="text-left">SKU</th>
                    <th class="text-left">Product</th>
                    <th class="text-left">Qty</th>
                    <th class="text-left">Cost</th>
                </tr>

                </thead>

                <tbody class="divide-y">

                @foreach($po->items as $item)

                    <tr>

                        <td class="py-2">
                            {{ $item->product->sku }}
                        </td>

                        <td>
                            {{ $item->product->name }}
                        </td>

                        <td>
                            {{ $item->quantity }}
                        </td>

                        <td>
                            €{{ number_format($item->cost_price,2) }}
                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>



        <!-- ADD ITEM -->

        @if($po->status === 'draft')

            <div class="bg-white border rounded p-6">

                <h2 class="font-semibold mb-4">
                    Add Item
                </h2>

                <form method="POST"
                      action="{{ route('purchase-orders.items.add',$po) }}"
                      class="flex gap-2">

                    @csrf

                    <select
                        name="product_id"
                        class="border rounded px-3 py-2"
                    >

                        @foreach($products as $product)

                            <option value="{{ $product->id }}">
                                {{ $product->sku }} — {{ $product->name }}
                            </option>

                        @endforeach

                    </select>

                    <input
                        type="number"
                        name="quantity"
                        placeholder="Qty"
                        class="border rounded px-3 py-2"
                    />

                    <input
                        type="number"
                        step="0.01"
                        name="cost_price"
                        placeholder="Cost price"
                        class="border rounded px-3 py-2"
                    />

                    <button
                        class="bg-blue-600 text-white px-4 py-2 rounded"
                    >
                        Add
                    </button>

                </form>

            </div>

        @endif



        <!-- ACTIONS -->

        <div class="flex gap-3">

            @if($po->status === 'draft')

                <form method="POST"
                      action="{{ route('purchase-orders.order',$po) }}">

                    @csrf

                    <button
                        class="bg-blue-600 text-white px-4 py-2 rounded"
                    >
                        Place Order
                    </button>

                </form>

            @endif


            @if($po->status === 'ordered')

                <form method="POST"
                      action="{{ route('purchase-orders.receive',$po) }}">

                    @csrf

                    <button
                        class="bg-green-600 text-white px-4 py-2 rounded"
                    >
                        Receive Goods
                    </button>

                </form>

            @endif

        </div>

    </div>

</x-app-layout>

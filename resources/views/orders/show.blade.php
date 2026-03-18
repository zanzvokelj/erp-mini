<x-app-layout>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif
    <div class="space-y-6">

        <!-- Breadcrumb -->

        <div class="flex items-center gap-3 text-sm">
            <a href="{{ route('orders.index') }}" class="text-gray-500 hover:text-gray-700">
                Orders
            </a>

            <span class="text-gray-400">/</span>

            <span class="font-semibold text-gray-800">
                #{{ $order->order_number }}
            </span>

        </div>


        <!-- Order Details -->

        <div class="bg-white border border-gray-200 rounded-lg p-6">

            <h2 class="text-sm font-semibold text-gray-700 mb-4">
                Order Details
            </h2>

            <div class="grid grid-cols-4 gap-6 text-sm">

                <div>
                    <p class="text-gray-500">Customer</p>
                    <p class="font-medium">{{ $order->customer->name }}</p>
                </div>

                <div>
                    <p class="text-gray-500">Warehouse</p>
                    <p class="font-medium">{{ $order->warehouse->name ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-gray-500">Status</p>
                    <p class="font-medium">{{ ucfirst($order->status) }}</p>
                </div>

                <div>
                    <p class="text-gray-500">Created</p>
                    <p class="font-medium">
                        {{ $order->created_at->format('M j, Y') }}
                    </p>
                </div>

                <div>
                    <p class="text-gray-500">Total</p>
                    <p class="font-medium">
                        €{{ number_format($order->total,2) }}
                    </p>
                </div>

            </div>

        </div>


        <!-- Order Items -->

        <div class="bg-white border border-gray-200 rounded-lg overflow-visible">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">Product</th>
                    <th class="text-left px-6 py-3">Price</th>
                    <th class="text-left px-6 py-3">Qty</th>
                    <th class="text-left px-6 py-3">Total</th>
                    @if($order->status === 'draft')
                        <th class="text-left px-6 py-3">Actions</th>
                    @endif
                </tr>
                </thead>

                <tbody class="divide-y">

                @foreach($order->items as $item)

                    <tr>

                        <td class="px-6 py-3">
                            <div class="flex flex-col">
                                <span>{{ $item->product->name }}</span>
                                <span class="text-xs text-gray-500">SKU: {{ $item->product->sku }}</span>
                            </div>
                        </td>

                        <td class="px-6 py-3">
                            €{{ number_format($item->price_at_time,2) }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $item->quantity }}
                        </td>

                        <td class="px-6 py-3 font-medium">
                            €{{ number_format($item->price_at_time * $item->quantity,2) }}
                        </td>

                        @if($order->status === 'draft')

                            <td class="px-6 py-3 flex gap-3">

                                <form method="POST" action="{{ route('orders.items.update',$item) }}">
                                    @csrf
                                    @method('PATCH')

                                    <input
                                        type="number"
                                        name="quantity"
                                        value="{{ $item->quantity }}"
                                        min="1"
                                        class="w-16 border rounded px-2 py-1 text-xs"
                                    />

                                    <button class="text-blue-600 text-xs">
                                        Update
                                    </button>

                                </form>


                                <form method="POST" action="{{ route('orders.items.remove',$item) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button class="text-red-600 text-xs">
                                        Remove
                                    </button>

                                </form>

                            </td>

                        @endif

                    </tr>

                @endforeach

                </tbody>

            </table>


            <!-- Add Product -->

            @if($order->status === 'draft')

                <div class="border-t p-6">

                    <h2 class="text-sm font-semibold text-gray-700 mb-4">
                        Add Product
                    </h2>

                    <form
                        method="POST"
                        action="{{ route('orders.items.add', $order) }}"
                        class="flex items-start gap-4"
                    >

                        @csrf

                        <select
                            id="product-select"
                            name="product_id"
                            placeholder="Search product..."
                            class="w-72 border border-gray-200 rounded-md px-3 py-2 text-sm"
                        ></select>


                        <div class="flex flex-col">

                            <input
                                type="number"
                                name="quantity"
                                id="qty-input"
                                value="1"
                                min="1"
                                class="w-20 border border-gray-200 rounded px-3 py-2 text-sm"
                            />

                            <div class="text-xs text-gray-500 mt-1" id="stock-info"></div>

                            <div class="text-xs text-red-600 hidden" id="stock-warning">
                                Not enough available stock
                            </div>

                        </div>


                        <button
                            class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
                        >
                            Add Item
                        </button>

                    </form>

                </div>

            @endif

        </div>


        <!-- Order Activity Timeline -->

        <div class="bg-white border border-gray-200 rounded-lg p-6">

            <h2 class="text-sm font-semibold text-gray-700 mb-4">
                Order Activity
            </h2>

            <div class="space-y-3">

                @foreach($order->activities as $activity)

                    <div class="flex items-center gap-3 text-sm">

                        <span>•</span>

                        <div class="text-gray-700">
                            {{ $activity->description }}
                        </div>

                        <div class="text-gray-400 text-xs">
                            {{ $activity->created_at->diffForHumans() }}
                        </div>

                    </div>

                @endforeach

            </div>

        </div>


        <!-- Order Actions -->

        <div class="flex justify-end gap-3">

            @if($order->status === 'draft')

                <form method="POST" action="{{ route('orders.confirm', $order) }}">
                    @csrf
                    <button class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                        Confirm Order
                    </button>
                </form>

            @endif


            @if($order->status === 'confirmed')

                <form method="POST" action="{{ route('orders.ship', $order) }}">
                    @csrf
                    <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                        Mark as Shipped
                    </button>
                </form>

            @endif


            @if(in_array($order->status, ['draft','confirmed']))

                <form method="POST" action="{{ route('orders.cancel',$order) }}">
                    @csrf
                    <button class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">
                        Cancel Order
                    </button>
                </form>

            @endif


                @php
                    $invoice = $order->invoice;
                    $isPaid = $invoice && $invoice->status === 'paid';
                @endphp

                @if($order->status === 'shipped')

                    <form method="POST" action="{{ route('orders.complete', $order) }}">
                        @csrf

                        <button
                            class="px-4 py-2 rounded text-sm
            {{ $isPaid ? 'bg-purple-600 hover:bg-purple-700 text-white' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}"
                            {{ $isPaid ? '' : 'disabled' }}
                        >
                            Mark as Completed
                        </button>

                    </form>

                @endif

                @if($order->status === 'shipped' && !$isPaid)
                    <p class="text-xs text-gray-500 mt-2">
                        Order must be fully paid before completion
                    </p>
                @endif


            @if($order->status === 'completed')

                <form method="POST" action="{{ route('orders.return', $order) }}">
                    @csrf
                    <button class="bg-orange-600 text-white px-4 py-2 rounded text-sm hover:bg-orange-700">
                        Mark as Returned
                    </button>
                </form>

            @endif

        </div>

    </div>


    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>


    <script>

        document.addEventListener("DOMContentLoaded", function () {

            let currentAvailable = 0;

            const qtyInput = document.getElementById("qty-input");
            const stockInfo = document.getElementById("stock-info");
            const stockWarning = document.getElementById("stock-warning");

            const productSelect = document.querySelector("#product-select");

            if(productSelect){

                const select = new TomSelect(productSelect,{

                    valueField: "id",
                    labelField: "name",
                    searchField: ["name","sku"],
                    preload: true,
                    openOnFocus: true,

                    load: function(query, callback) {

                        fetch(`/api/products/search?q=${query}&warehouse_id={{ $order->warehouse_id }}`)
                            .then(response => response.json())
                            .then(json => {

                                const results = json.map(product => ({
                                    id: product.id,
                                    name: `${product.name} — ${product.sku} — €${product.price}
                        (Stock: ${product.stock} | Reserved: ${product.reserved} | Available: ${product.available})`,
                                    stock: product.stock,
                                    reserved: product.reserved,
                                    available: product.available
                                }));

                                callback(results);

                            })
                            .catch(() => callback());

                    },

                    onChange: function(value){

                        const option = this.options[value];

                        currentAvailable = option.available || 0;

                        stockInfo.innerText =
                            `Stock: ${option.stock} | Reserved: ${option.reserved} | Available: ${option.available}`;

                        validateQty();
                    }

                });

            }

        });

    </script>

</x-app-layout>

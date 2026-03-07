<x-app-layout>

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
                            {{ $item->product->name }}
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
                        class="flex items-center gap-4"
                    >

                        @csrf

                        <select
                            id="product-select"
                            name="product_id"
                            placeholder="Search product..."
                            class="w-72 border border-gray-200 rounded-md px-3 py-2 text-sm"
                        ></select>

                        <input
                            type="number"
                            name="quantity"
                            value="1"
                            min="1"
                            class="w-20 border border-gray-200 rounded px-3 py-2 text-sm"
                        />

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

                        @if($activity->type === 'created')
                            <span>🟢</span>
                        @elseif($activity->type === 'item_added')
                            <span>➕</span>
                        @elseif($activity->type === 'item_updated')
                            <span>✏️</span>
                        @elseif($activity->type === 'item_removed')
                            <span>🗑</span>
                        @elseif($activity->type === 'confirmed')
                            <span>📦</span>
                        @elseif($activity->type === 'shipped')
                            <span>🚚</span>
                        @elseif($activity->type === 'completed')
                            <span>✅</span>
                        @elseif($activity->type === 'cancelled')
                            <span>❌</span>
                        @else
                            <span>•</span>
                        @endif

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

                    <button
                        class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700"
                    >
                        Confirm Order
                    </button>

                </form>

            @endif


            @if($order->status === 'confirmed')

                <form method="POST" action="{{ route('orders.ship', $order) }}">
                    @csrf

                    <button
                        class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700"
                    >
                        Mark as Shipped
                    </button>

                </form>

                <form method="POST" action="{{ route('orders.cancel',$order) }}">
                    @csrf

                    <button
                        class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700"
                    >
                        Cancel Order
                    </button>

                </form>

            @endif


            @if($order->status === 'shipped')

                <form method="POST" action="{{ route('orders.complete', $order) }}">
                    @csrf

                    <button
                        class="bg-purple-600 text-white px-4 py-2 rounded text-sm hover:bg-purple-700"
                    >
                        Mark as Completed
                    </button>

                </form>

            @endif

        </div>

    </div>


    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>


    <script>

        document.addEventListener("DOMContentLoaded", function () {

            new TomSelect("#product-select",{

                valueField: "id",
                labelField: "name",
                searchField: ["name","sku"],

                preload: true,
                openOnFocus: true,

                load: function(query, callback) {

                    fetch(`/api/products/search?q=${query}`)
                        .then(response => response.json())
                        .then(json => {

                            const results = json.map(product => ({
                                id: product.id,
                                name: `${product.name} — ${product.sku} — €${product.price}`
                            }));

                            callback(results);

                        })
                        .catch(() => callback());

                }

            });

        });

    </script>

</x-app-layout>

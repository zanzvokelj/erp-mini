<x-app-layout>

    @php

        $statusClasses = [
        'draft' => 'bg-gray-100 text-gray-700',
        'confirmed' => 'bg-green-100 text-green-700',
        'shipped' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-purple-100 text-purple-700',
        'cancelled' => 'bg-red-400 text-red-800',
        ];

    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-gray-800">
            Orders
        </h2>
    </x-slot>

    <div class="space-y-6">

        <!-- Page header -->

        <div class="flex items-center justify-between">

            <h1 class="text-lg font-semibold text-gray-800">
                Orders
            </h1>

            <a
                href="{{ route('orders.create') }}"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
                Create Order
            </a>

        </div>


        <!-- Filters -->

        <div class="bg-white border border-gray-200 rounded-lg p-4">

            <form method="GET" action="{{ route('orders.index') }}">

                <div class="flex items-center gap-4">

                    <input
                        name="search"
                        value="{{ request('search') }}"
                        type="text"
                        placeholder="Search orders..."
                        class="w-80 border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                    />

                    <select name="customer">

                        <option value="">All Customers</option>

                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ request('customer') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach

                    </select>

                    <select name="status">

                        <option value="">All Statuses</option>

                        <option value="draft">Draft</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="shipped">Shipped</option>
                        <option value="completed">Completed</option>

                    </select>

                    <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md">
                        Filter
                    </button>

                    <a
                        href="{{ route('orders.index') }}"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-md bg-white hover:bg-gray-50"
                    >
                        Clear
                    </a>

                </div>

            </form>

        </div>


        <!-- Orders table -->

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">Order</th>
                    <th class="text-left px-6 py-3">Customer</th>
                    <th class="text-left px-6 py-3">Status</th>
                    <th class="text-left px-6 py-3">Total</th>
                    <th class="text-left px-6 py-3">Date</th>
                    <th class="text-left px-6 py-3"></th>
                </tr>
                </thead>
                @foreach($orders as $order)

                    <tr>

                        <td class="px-6 py-3 font-medium">
                            #{{ $order->order_number }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $order->customer->name }}
                        </td>

                        <td class="px-6 py-3">

<span class="px-2 py-1 text-xs rounded {{ $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-700' }}">

{{ ucfirst($order->status) }}

</span>

                        </td>

                        <td class="px-6 py-3">
                            €{{ number_format($order->total,2) }}
                        </td>

                        <td class="px-6 py-3 text-gray-500">
                            {{ $order->created_at->format('M j, Y') }}
                        </td>

                        <td class="px-6 py-3">

                            <a
                                href="{{ route('orders.show',$order) }}"
                                class="px-3 py-1 text-xs border border-gray-200 rounded hover:bg-gray-50"
                            >

                                View

                            </a>

                        </td>

                    </tr>

                @endforeach

                </thead>

            </table>
            </div>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>

        </div>


    </div>

</x-app-layout>

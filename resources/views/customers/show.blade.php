<x-app-layout>

    <div class="space-y-6">

        <!-- Breadcrumb -->

        <div class="flex items-center gap-3 text-sm">
            <a href="{{ route('customers.index') }}" class="text-gray-500 hover:text-gray-700">
                Customers
            </a>

            <span class="text-gray-400">/</span>

            <span class="font-semibold text-gray-800">
                {{ $customer->name }}
            </span>
        </div>


        <!-- Customer Details -->

        <div class="bg-white border border-gray-200 rounded-lg p-6">

            <h2 class="text-sm font-semibold text-gray-700 mb-4">
                Customer Details
            </h2>

            <div class="grid grid-cols-4 gap-6 text-sm">

                <div>
                    <p class="text-gray-500">Name</p>
                    <p class="font-medium">{{ $customer->name }}</p>
                </div>

                <div>
                    <p class="text-gray-500">Type</p>

                    <span class="px-2 py-1 text-xs rounded
                    {{ $customer->type === 'wholesale'
                        ? 'bg-blue-100 text-blue-700'
                        : 'bg-gray-200 text-gray-700' }}">

                        {{ ucfirst($customer->type) }}

                    </span>
                </div>

                <div>
                    <p class="text-gray-500">Discount</p>
                    <p class="font-medium">{{ $customer->discount_percent }}%</p>
                </div>

                <div>
                    <p class="text-gray-500">Credit Limit</p>
                    <p class="font-medium">
                        €{{ number_format($customer->credit_limit,2) }}
                    </p>
                </div>

            </div>

        </div>


        <!-- Customer Stats -->

        <div class="grid grid-cols-3 gap-6">

            <div class="bg-white border border-gray-200 rounded-lg p-6">

                <p class="text-sm text-gray-500">Total Orders</p>

                <p class="text-xl font-semibold text-gray-800 mt-1">
                    {{ $stats->total_orders ?? 0 }}
                </p>

            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">

                <p class="text-sm text-gray-500">Total Revenue</p>

                <p class="text-xl font-semibold text-gray-800 mt-1">
                    €{{ number_format($stats->total_revenue ?? 0,2) }}
                </p>

            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">

                <p class="text-sm text-gray-500">Average Order</p>

                <p class="text-xl font-semibold text-gray-800 mt-1">
                    €{{ number_format($stats->avg_order ?? 0,2) }}
                </p>

            </div>

        </div>


        <!-- Customer Orders -->

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-200">

                <h2 class="text-sm font-semibold text-gray-700">
                    Orders
                </h2>

            </div>

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">

                <tr>
                    <th class="text-left px-6 py-3">Order</th>
                    <th class="text-left px-6 py-3">Status</th>
                    <th class="text-left px-6 py-3">Total</th>
                    <th class="text-left px-6 py-3">Date</th>
                    <th class="text-left px-6 py-3"></th>
                </tr>

                </thead>

                <tbody class="divide-y">

                @forelse($orders as $order)

                    <tr>

                        <td class="px-6 py-3 font-medium">
                            {{ $order->order_number }}
                        </td>

                        <td class="px-6 py-3">

<span class="px-2 py-1 text-xs rounded
@if($order->status === 'completed')
bg-green-100 text-green-700
@elseif($order->status === 'shipped')
bg-blue-100 text-blue-700
@elseif($order->status === 'confirmed')
bg-yellow-100 text-yellow-700
@elseif($order->status === 'cancelled')
bg-red-100 text-red-700
@elseif($order->status === 'returned')
bg-orange-100 text-orange-700
@else
bg-gray-200 text-gray-700
@endif">

{{ ucfirst($order->status) }}

</span>

                        </td>

                        <td class="px-6 py-3">
                            €{{ number_format($order->total,2) }}
                        </td>

                        <td class="px-6 py-3 text-gray-500">
                            {{ \Carbon\Carbon::parse($order->created_at)->format('M j, Y') }}
                        </td>

                        <td class="px-6 py-3">

                            <a
                                href="{{ route('orders.show',$order->id) }}"
                                class="px-3 py-1 text-xs border border-gray-200 rounded hover:bg-gray-50"
                            >
                                View
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" class="px-6 py-6 text-center text-gray-500">

                            No orders found

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        <!-- Actions -->

        <div class="flex justify-end">

            <a
                href="{{ route('orders.create') }}"
                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
                Create Order
            </a>

        </div>

    </div>

</x-app-layout>

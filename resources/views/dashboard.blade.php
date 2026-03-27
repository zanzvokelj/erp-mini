<x-app-layout>
    @php

        $statusClasses = [
            'draft' => 'bg-gray-100 text-gray-700',
            'confirmed' => 'bg-green-100 text-green-700',
            'shipped' => 'bg-blue-100 text-blue-700',
            'completed' => 'bg-purple-100 text-purple-700',
            'cancelled' => 'bg-red-100 text-purple-700',
        ];

    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-gray-800">
            Dashboard
        </h2>
    </x-slot>

    <div class="container space-y-6">

        <!-- KPI cards -->
        <div class="grid grid-cols-5 gap-6">

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Revenue</p>
                <p class="text-xl font-semibold text-gray-900">€{{ number_format($revenue, 2) }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Orders</p>
                <p class="text-xl font-semibold text-gray-900">{{ number_format($ordersCount) }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Avg Order Value</p>
                <p class="text-xl font-semibold text-gray-900">€{{ number_format($avgOrderValue, 2) }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Low Stock</p>
                <p class="text-xl font-semibold text-red-500">{{ $lowStockCount }}</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Inventory Value</p>
                <p class="text-xl font-semibold text-gray-900">
                    €{{ number_format($inventoryValue, 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Paid For Inventory</p>
                <p class="text-xl font-semibold text-gray-900">
                    €{{ number_format($paidForInventory, 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Orders Today</p>
                <p class="text-xl font-semibold text-gray-900">
                    {{ $ordersToday }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Revenue Today</p>
                <p class="text-xl font-semibold text-gray-900">
                    €{{ number_format($revenueToday, 2) }}
                </p>
            </div>


            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Pending Orders</p>
                <p class="text-xl font-semibold text-gray-900">
                    {{ $pendingOrders }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <p class="text-xs text-gray-500">Profit</p>
                <p class="text-xl font-semibold text-green-600">
                    €{{ number_format($totalProfit, 2) }}
                </p>
            </div>

        </div>


        <!-- Chart -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">

            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-700">
                    Revenue (Last 6 Months)
                </h2>
            </div>

            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>

        </div>


        <!-- Recent Orders -->
        <div class="bg-white border border-gray-200 rounded-lg">

            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">
                    Recent Orders
                </h2>
            </div>

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">Order</th>
                    <th class="text-left px-6 py-3">Customer</th>
                    <th class="text-left px-6 py-3">Status</th>
                    <th class="text-left px-6 py-3">Total</th>
                    <th class="text-left px-6 py-3">Date</th>
                </tr>
                </thead>

                <tbody class="divide-y">

                @foreach($recentOrders as $order)

                    <tr>

                        <td class="px-6 py-3">
                            {{ $order->order_number }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $order->customer->name }}
                        </td>

                        <td class="px-6 py-3">

<span class="px-2 py-1 text-xs rounded-full font-medium
{{ $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-700' }}">

{{ ucfirst($order->status) }}

</span>

                        </td>

                        <td class="px-6 py-3">
                            €{{ number_format($order->total, 2) }}
                        </td>

                        <td class="px-6 py-3 text-gray-500">
                            {{ $order->created_at->format('M d') }}
                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>


        <!-- Low Stock Products -->
        <div class="bg-white border border-gray-200 rounded-lg">

            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-700">
                    Low Stock Products
                </h2>
            </div>

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">Product</th>
                    <th class="text-left px-6 py-3">Stock</th>
                    <th class="text-left px-6 py-3">Min Stock</th>
                </tr>
                </thead>

                <tbody class="divide-y">

                @foreach($lowStockProducts as $product)

                    <tr>

                        <td class="px-6 py-3">
                            {{ $product->name }}
                        </td>

                        <td class="px-6 py-3 text-red-600 font-medium">
                            {{ $product->stock }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $product->min_stock }}
                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

        <div class="grid grid-cols-4 gap-6">

            <!-- Top Products -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">

                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    Top Selling Products
                </h2>

                <table class="w-full text-sm">

                    <thead class="text-gray-500">
                    <tr>
                        <th class="text-left py-2">Product</th>
                        <th class="text-right py-2">Sold</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y">

                    @foreach($topProducts as $product)

                        <tr>
                            <td class="py-2">{{ $product->name }}</td>
                            <td class="py-2 text-right font-medium">{{ $product->sold }}</td>
                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <!-- Top Customers -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">

                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    Top Customers
                </h2>

                <table class="w-full text-sm">

                    <thead class="text-gray-500">
                    <tr>
                        <th class="text-left py-2">Customer</th>
                        <th class="text-right py-2">Revenue</th>
                    </tr>
                    </thead>

                    <tbody class="divide-y">

                    @foreach($topCustomers as $customer)

                        <tr>
                            <td class="py-2">{{ $customer->name }}</td>
                            <td class="py-2 text-right font-medium">
                                €{{ number_format($customer->revenue, 2) }}
                            </td>
                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>


            <!-- Stock Turnover -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">

                <p class="text-xs text-gray-500 mb-2">Stock Turnover</p>

                <p class="text-3xl font-semibold text-gray-900">
                    {{ number_format($stockTurnover, 2) }}
                </p>

            </div>


            <!-- Revenue Growth -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">

                <p class="text-xs text-gray-500 mb-2">Revenue Growth</p>

                <p class="text-3xl font-semibold
        {{ $revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}">

                    {{ number_format($revenueGrowth,1) }}%

                </p>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const monthlyRevenue = @json($monthlyRevenue);

        function loadDashboardChart() {
            const revenueData = Array.isArray(monthlyRevenue) ? monthlyRevenue : [];
            const labels = revenueData.map(item => item.label);
            const values = revenueData.map(item => Number(item.revenue ?? 0));

            const ctx = document.getElementById('revenueChart');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: values,
                        backgroundColor: 'rgba(37, 99, 235, 0.75)',
                        borderColor: 'rgba(29, 78, 216, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 48,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                        },
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

        }

        loadDashboardChart();

    </script>


</x-app-layout>

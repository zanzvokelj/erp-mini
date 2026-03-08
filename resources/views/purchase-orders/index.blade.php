<x-app-layout>

    <div class="space-y-6">

        <div class="flex justify-between items-center">

            <h1 class="text-lg font-semibold">
                Purchase Orders
            </h1>

            <a
                href="{{ route('purchase-orders.create') }}"
                class="bg-blue-600 text-white px-4 py-2 rounded text-sm"
            >
                Create Purchase Order
            </a>

        </div>

        <div class="bg-white border rounded-lg overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">

                <tr>
                    <th class="px-6 py-3 text-left">PO</th>
                    <th class="px-6 py-3 text-left">Supplier</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Total</th>
                    <th class="px-6 py-3 text-left">Date</th>
                </tr>

                </thead>

                <tbody class="divide-y">

                @foreach($purchaseOrders as $po)

                    <tr>

                        <td class="px-6 py-3">
                            <a
                                href="{{ route('purchase-orders.show',$po) }}"
                                class="text-blue-600"
                            >
                                {{ $po->po_number }}
                            </a>
                        </td>

                        <td class="px-6 py-3">
                            {{ $po->supplier->name }}
                        </td>

                        <td class="px-6 py-3">
                            {{ ucfirst($po->status) }}
                        </td>

                        <td class="px-6 py-3">
                            €{{ number_format($po->total,2) }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $po->created_at->format('M d, Y') }}
                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

    </div>

</x-app-layout>

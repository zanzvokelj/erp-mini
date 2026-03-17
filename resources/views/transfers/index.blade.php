<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-gray-800">
            Transfers
        </h2>
    </x-slot>

    <div class="space-y-6">

        <a href="{{ route('transfers.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
            New Transfer
        </a>

        <div class="bg-white border rounded-lg overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="px-6 py-3 text-left">Product</th>
                    <th class="px-6 py-3 text-left">From</th>
                    <th class="px-6 py-3 text-left">To</th>
                    <th class="px-6 py-3 text-left">Qty</th>
                    <th class="px-6 py-3 text-left">Date</th>
                </tr>
                </thead>

                <tbody class="divide-y">

                @foreach($transfers as $t)

                    <tr>
                        <td class="px-6 py-3">{{ $t->product->sku ?? '-' }} — {{ $t->product->name ?? '' }}</td>
                        <td class="px-6 py-3">{{ $t->fromWarehouse->name ?? '-' }}</td>
                        <td class="px-6 py-3">{{ $t->toWarehouse->name ?? '-' }}</td>
                        <td class="px-6 py-3">{{ $t->quantity }}</td>
                        <td class="px-6 py-3">{{ $t->created_at->diffForHumans() }}</td>
                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

    </div>

</x-app-layout>

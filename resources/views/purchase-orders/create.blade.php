<x-app-layout>

    <div class="max-w-xl">

        <h1 class="text-lg font-semibold mb-4">
            Create Purchase Order
        </h1>

        <form method="POST" action="{{ route('purchase-orders.store') }}">

            @csrf

            <select
                name="supplier_id"
                class="w-full border rounded px-3 py-2 mb-4"
            >

                @foreach($suppliers as $supplier)

                    <option value="{{ $supplier->id }}">
                        {{ $supplier->name }}
                    </option>

                @endforeach

            </select>

            <select
                name="warehouse_id"
                class="w-full border rounded px-3 py-2 mb-4"
            >

                @foreach($warehouses as $warehouse)

                    <option value="{{ $warehouse->id }}">
                        {{ $warehouse->name }}
                    </option>

                @endforeach

            </select>

            <button
                class="bg-blue-600 text-white px-4 py-2 rounded"
            >
                Create
            </button>

        </form>

    </div>

</x-app-layout>

<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-gray-800">
            Create Order
        </h2>
    </x-slot>

    <div class="space-y-6 max-w-xl">

        <div class="bg-white border border-gray-200 rounded-lg p-6">

            <form method="POST" action="{{ route('orders.store') }}">

                @csrf

                <div class="space-y-4">

                    <!-- Customer -->

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">
                            Customer
                        </label>

                        <select
                            id="customer-select"
                            name="customer_id"
                            required
                            class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
                        >

                        </select>

                    </div>

                    <!-- Warehouse -->

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">
                            Warehouse
                        </label>

                        <select
                            id="warehouse-select"
                            name="warehouse_id"
                            required
                            class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
                        >

                            @foreach($warehouses as $warehouse)

                                <option value="{{ $warehouse->id }}">
                                    {{ $warehouse->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <!-- Buttons -->

                    <div class="flex items-center gap-3 pt-4">

                        <button
                            class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            Create Draft Order
                        </button>

                        <a
                            href="{{ route('orders.index') }}"
                            class="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Cancel
                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>

    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>


    <script>

        new TomSelect("#customer-select",{
            valueField: "id",
            labelField: "name",
            searchField: ["name"],
            create:false,
            preload: false,
            load: function(query, callback) {
                apiFetch(`/api/customers/search?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => callback(data))
                    .catch(() => callback());
            },
            render: {
                option: function(item, escape) {
                    return `<div>${escape(item.name)} <span class="text-xs text-gray-500">(${escape(item.type ?? '')})</span></div>`;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name)}</div>`;
                }
            }
        });

        new TomSelect("#warehouse-select",{
            create:false
        });

    </script>
</x-app-layout>

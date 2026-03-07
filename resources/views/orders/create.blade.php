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

                            <option value="">Select customer</option>

                            @foreach($customers as $customer)

                                <option value="{{ $customer->id }}">
                                    {{ $customer->name }}
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
            create:false,
            sortField:{
                field:"text",
                direction:"asc"
            }
        });

    </script>
</x-app-layout>

<x-app-layout>

    <div class="space-y-6">

        <h1 class="text-lg font-semibold">
            Reorder Suggestions
        </h1>

        <div class="bg-white border rounded-lg overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="px-6 py-3 text-left">Product</th>
                    <th class="px-6 py-3 text-left">Stock</th>
                    <th class="px-6 py-3 text-left">Runout</th>
                    <th class="px-6 py-3 text-left">Suggested Order</th>
                    <th class="px-6 py-3 text-left">Action</th>
                </tr>
                </thead>

                <tbody class="divide-y">

                @foreach($suggestions as $s)

                    <tr>


                        <td class="px-6 py-3">
                            {{ $s['product']->name }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $s['stock'] }}
                        </td>

                        <td class="px-6 py-3 text-red-600">
                            {{ $s['runout'] }} days
                        </td>

                        <td class="px-6 py-3 font-medium">
                            {{ $s['suggested_qty'] }}
                        </td>
                        <td class="px-6 py-3">

                            <form method="POST" action="{{ route('reorder.createPO') }}">

                                @csrf

                                <input type="hidden" name="product_id" value="{{ $s['product']->id }}">
                                <input type="hidden" name="quantity" value="{{ $s['suggested_qty'] }}">

                                <button
                                    class="bg-blue-600 text-white px-3 py-1 rounded text-sm"
                                >
                                    Create PO
                                </button>

                            </form>

                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

    </div>

</x-app-layout>

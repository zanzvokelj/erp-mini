<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">VAT Summary</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Output VAT, input VAT and net tax position for the selected period.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('finance.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Back to Finance
                </a>

                <a
                    href="{{ route('finance.balance-sheet.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Balance Sheet
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <form method="GET" action="{{ route('finance.vat-summary.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date From</label>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ request('date_from') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date To</label>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ request('date_to') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                <div class="flex items-end gap-2 md:col-span-2">
                    <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Filter
                    </button>
                    <a
                        href="{{ route('finance.vat-summary.index') }}"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                    >
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Output VAT</p>
                <p class="text-2xl font-semibold text-gray-900 mt-2">
                    €{{ number_format((float) $summary['output_vat'], 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Input VAT</p>
                <p class="text-2xl font-semibold text-gray-900 mt-2">
                    €{{ number_format((float) $summary['input_vat'], 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Net VAT Payable</p>
                <p class="text-2xl font-semibold mt-2 text-red-600">
                    €{{ number_format((float) $summary['net_vat_liability'], 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Net VAT Receivable</p>
                <p class="text-2xl font-semibold mt-2 text-green-600">
                    €{{ number_format((float) $summary['net_vat_receivable'], 2) }}
                </p>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">Code</th>
                    <th class="text-left px-6 py-3">Account</th>
                    <th class="text-left px-6 py-3">Direction</th>
                    <th class="text-right px-6 py-3">Amount</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($accounts as $account)
                    <tr>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $account['code'] }}</td>
                        <td class="px-6 py-3 text-gray-800">{{ $account['name'] }}</td>
                        <td class="px-6 py-3 text-gray-500 capitalize">{{ $account['direction'] }}</td>
                        <td class="px-6 py-3 text-right text-gray-800">€{{ number_format((float) $account['amount'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>

</x-app-layout>

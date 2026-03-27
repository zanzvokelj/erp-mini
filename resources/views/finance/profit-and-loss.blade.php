<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Profit &amp; Loss</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Revenue and expense performance derived from posted journal entries.
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

                <a
                    href="{{ route('finance.trial-balance.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Trial Balance
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <form method="GET" action="{{ route('finance.profit-and-loss.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                        href="{{ route('finance.profit-and-loss.index') }}"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                    >
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Revenue</p>
                <p class="text-2xl font-semibold text-gray-900 mt-2">
                    €{{ number_format((float) $summary['revenue'], 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Expenses</p>
                <p class="text-2xl font-semibold text-gray-900 mt-2">
                    €{{ number_format((float) $summary['expenses'], 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Gross Profit</p>
                <p class="text-2xl font-semibold mt-2 {{ $summary['gross_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    €{{ number_format((float) $summary['gross_profit'], 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Net Profit</p>
                <p class="text-2xl font-semibold mt-2 {{ $summary['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    €{{ number_format((float) $summary['net_profit'], 2) }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-900">Revenue</h2>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="text-left px-6 py-3">Code</th>
                        <th class="text-left px-6 py-3">Account</th>
                        <th class="text-right px-6 py-3">Amount</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($revenue_accounts as $account)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $account['code'] }}</td>
                            <td class="px-6 py-3 text-gray-800">{{ $account['name'] }}</td>
                            <td class="px-6 py-3 text-right text-gray-800">€{{ number_format((float) $account['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                No revenue accounts in the selected range.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="2" class="px-6 py-3 text-right font-semibold text-gray-700">Total Revenue</td>
                        <td class="px-6 py-3 text-right font-semibold text-gray-900">
                            €{{ number_format((float) $summary['revenue'], 2) }}
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-900">Expenses</h2>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="text-left px-6 py-3">Code</th>
                        <th class="text-left px-6 py-3">Account</th>
                        <th class="text-right px-6 py-3">Amount</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @forelse($expense_accounts as $account)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $account['code'] }}</td>
                            <td class="px-6 py-3 text-gray-800">{{ $account['name'] }}</td>
                            <td class="px-6 py-3 text-right text-gray-800">€{{ number_format((float) $account['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                No expense accounts in the selected range.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td colspan="2" class="px-6 py-3 text-right font-semibold text-gray-700">Total Expenses</td>
                        <td class="px-6 py-3 text-right font-semibold text-gray-900">
                            €{{ number_format((float) $summary['expenses'], 2) }}
                        </td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

</x-app-layout>

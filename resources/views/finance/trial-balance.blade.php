<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Trial Balance</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Account totals derived from posted journal lines.
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
                    href="{{ route('finance.journal-entries.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    View Journal
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <form method="GET" action="{{ route('finance.trial-balance.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                        href="{{ route('finance.trial-balance.index') }}"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                    >
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Total Debit</p>
                <p class="text-2xl font-semibold text-gray-900 mt-2">
                    €{{ number_format((float) $totals['total_debit'], 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Total Credit</p>
                <p class="text-2xl font-semibold text-gray-900 mt-2">
                    €{{ number_format((float) $totals['total_credit'], 2) }}
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <p class="text-sm text-gray-500">Status</p>
                <p class="text-2xl font-semibold mt-2 {{ $totals['is_balanced'] ? 'text-green-600' : 'text-red-600' }}">
                    {{ $totals['is_balanced'] ? 'Balanced' : 'Out of Balance' }}
                </p>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">Code</th>
                    <th class="text-left px-6 py-3">Account</th>
                    <th class="text-left px-6 py-3">Type</th>
                    <th class="text-right px-6 py-3">Debit</th>
                    <th class="text-right px-6 py-3">Credit</th>
                    <th class="text-right px-6 py-3">Balance</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($accounts as $account)
                    <tr>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $account['code'] }}</td>
                        <td class="px-6 py-3 text-gray-800">{{ $account['name'] }}</td>
                        <td class="px-6 py-3 text-gray-500 capitalize">{{ $account['type'] }}</td>
                        <td class="px-6 py-3 text-right text-gray-800">€{{ number_format((float) $account['total_debit'], 2) }}</td>
                        <td class="px-6 py-3 text-right text-gray-800">€{{ number_format((float) $account['total_credit'], 2) }}</td>
                        <td class="px-6 py-3 text-right text-gray-800">
                            @if((float) $account['balance_amount'] === 0.0)
                                -
                            @else
                                €{{ number_format((float) $account['balance_amount'], 2) }}
                                {{ ucfirst($account['balance_side']) }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No accounts are available.
                        </td>
                    </tr>
                @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                <tr>
                    <td colspan="3" class="px-6 py-3 text-right font-semibold text-gray-700">Total</td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-900">
                        €{{ number_format((float) $totals['total_debit'], 2) }}
                    </td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-900">
                        €{{ number_format((float) $totals['total_credit'], 2) }}
                    </td>
                    <td class="px-6 py-3 text-right font-semibold {{ $totals['is_balanced'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $totals['is_balanced'] ? 'Balanced' : 'Out of Balance' }}
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>

    </div>

</x-app-layout>

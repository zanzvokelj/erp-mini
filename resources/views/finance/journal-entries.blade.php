<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Journal Entries</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Read-only general ledger view generated as accounting side effects.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('finance.balance-sheet.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Balance Sheet
                </a>

                <a
                    href="{{ route('finance.profit-and-loss.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Profit &amp; Loss
                </a>

                <a
                    href="{{ route('finance.trial-balance.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Trial Balance
                </a>

                <a
                    href="{{ route('finance.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Back to Finance
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <form method="GET" action="{{ route('finance.journal-entries.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Entry Type</label>
                    <select name="entry_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">All entry types</option>
                        @foreach($entryTypes as $entryType)
                            <option value="{{ $entryType }}" {{ request('entry_type') === $entryType ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', $entryType) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Reference Type</label>
                    <select name="reference_type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">All references</option>
                        @foreach($referenceTypes as $referenceType)
                            <option value="{{ $referenceType }}" {{ request('reference_type') === $referenceType ? 'selected' : '' }}>
                                {{ class_basename($referenceType) }}
                            </option>
                        @endforeach
                    </select>
                </div>

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

                <div class="flex items-end gap-2">
                    <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Filter
                    </button>
                    <a
                        href="{{ route('finance.journal-entries.index') }}"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                    >
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @forelse($entries as $entry)
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

                    <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900">{{ $entry->entry_number }}</span>
                                <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                    {{ str_replace('_', ' ', $entry->entry_type) }}
                                </span>
                                @if($entry->reversal_of_journal_entry_id)
                                    <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-700">
                                        Reversal
                                    </span>
                                @endif
                                @if($entry->reversalEntry)
                                    <span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">
                                        Reversed
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $entry->description ?: 'Journal entry' }}
                            </p>
                        </div>

                        <div class="text-sm text-gray-500 text-right">
                            <div>Posted: {{ optional($entry->posted_at)->format('Y-m-d H:i') }}</div>
                            <div>Ref: {{ class_basename($entry->reference_type) }} #{{ $entry->reference_id }}</div>
                            @if(! $entry->reversal_of_journal_entry_id && ! $entry->reversalEntry)
                                <form method="POST" action="{{ route('finance.journal-entries.reverse', $entry) }}" class="mt-2">
                                    @csrf
                                    <button class="px-3 py-2 text-xs border border-gray-200 rounded-lg bg-white hover:bg-gray-50">
                                        Reverse Entry
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="text-left px-6 py-3">Account</th>
                            <th class="text-left px-6 py-3">Code</th>
                            <th class="text-right px-6 py-3">Debit</th>
                            <th class="text-right px-6 py-3">Credit</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        @foreach($entry->lines->sortBy('line_number') as $line)
                            <tr>
                                <td class="px-6 py-3 font-medium text-gray-800">
                                    {{ $line->account->name }}
                                </td>
                                <td class="px-6 py-3 text-gray-500">
                                    {{ $line->account->code }}
                                </td>
                                <td class="px-6 py-3 text-right text-gray-800">
                                    @if((float) $line->debit > 0)
                                        €{{ number_format((float) $line->debit, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right text-gray-800">
                                    @if((float) $line->credit > 0)
                                        €{{ number_format((float) $line->credit, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-right font-semibold text-gray-700">
                                Total
                            </td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900">
                                €{{ number_format((float) $entry->lines->sum('debit'), 2) }}
                            </td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900">
                                €{{ number_format((float) $entry->lines->sum('credit'), 2) }}
                            </td>
                        </tr>
                        </tfoot>
                    </table>

                </div>
            @empty
                <div class="bg-white border border-gray-200 rounded-xl p-8 text-center text-gray-500">
                    No journal entries have been posted yet.
                </div>
            @endforelse
        </div>

        <div>
            {{ $entries->links() }}
        </div>

    </div>

</x-app-layout>

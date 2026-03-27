<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Chart of Accounts</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Read-only account list used by the accounting layer.
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

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">Code</th>
                    <th class="text-left px-6 py-3">Account</th>
                    <th class="text-left px-6 py-3">Type</th>
                    <th class="text-right px-6 py-3">Journal Lines</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($accounts as $account)
                    <tr>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $account->code }}</td>
                        <td class="px-6 py-3 text-gray-800">{{ $account->name }}</td>
                        <td class="px-6 py-3 text-gray-500 capitalize">{{ $account->type }}</td>
                        <td class="px-6 py-3 text-right text-gray-800">{{ $account->journal_lines_count }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div>
            {{ $accounts->links() }}
        </div>

    </div>

</x-app-layout>

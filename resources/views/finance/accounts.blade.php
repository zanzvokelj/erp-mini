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

                <a
                    href="{{ route('finance.accounts.create') }}"
                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    New Account
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">Code</th>
                    <th class="text-left px-6 py-3">Account</th>
                    <th class="text-left px-6 py-3">Type</th>
                    <th class="text-left px-6 py-3">Category</th>
                    <th class="text-left px-6 py-3">Subtype</th>
                    <th class="text-left px-6 py-3">Status</th>
                    <th class="text-right px-6 py-3">Journal Lines</th>
                    <th class="text-right px-6 py-3">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($accounts as $account)
                    <tr>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $account->code }}</td>
                        <td class="px-6 py-3 text-gray-800">{{ $account->name }}</td>
                        <td class="px-6 py-3 text-gray-500 capitalize">{{ $account->type }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $account->category ? str_replace('_', ' ', $account->category) : '-' }}</td>
                        <td class="px-6 py-3 text-gray-500">{{ $account->subtype ?: '-' }}</td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-1 rounded-full text-xs {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                {{ $account->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right text-gray-800">{{ $account->journal_lines_count }}</td>
                        <td class="px-6 py-3">
                            <div class="flex justify-end gap-2">
                                <a
                                    href="{{ route('finance.accounts.edit', $account) }}"
                                    class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                                >
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('finance.accounts.toggle', $account) }}">
                                    @csrf
                                    <button class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50">
                                        {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
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

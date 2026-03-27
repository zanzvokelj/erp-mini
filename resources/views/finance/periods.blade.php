<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Accounting Periods</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Close periods to block new journal postings for those dates.
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

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <form method="GET" action="{{ route('finance.periods.index') }}" class="flex items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Year</label>
                    <input
                        type="number"
                        name="year"
                        value="{{ $selectedYear }}"
                        class="border border-gray-200 rounded-lg px-3 py-2 text-sm"
                    >
                </div>

                <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Load Periods
                </button>
            </form>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="text-left px-6 py-3">Period</th>
                    <th class="text-left px-6 py-3">Range</th>
                    <th class="text-left px-6 py-3">Status</th>
                    <th class="text-left px-6 py-3">Closed By</th>
                    <th class="text-left px-6 py-3">Closed At</th>
                    <th class="text-right px-6 py-3">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @foreach($periods as $period)
                    <tr>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $period->name }}</td>
                        <td class="px-6 py-3 text-gray-600">
                            {{ $period->start_date->format('Y-m-d') }} to {{ $period->end_date->format('Y-m-d') }}
                        </td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-1 rounded-full text-xs {{ $period->status === 'closed' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                {{ ucfirst($period->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $period->closedBy?->email ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ optional($period->closed_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="px-6 py-3 text-right">
                            @if($period->status === 'open')
                                <form method="POST" action="{{ route('finance.periods.close', $period) }}">
                                    @csrf
                                    <button class="px-3 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                                        Close
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('finance.periods.reopen', $period) }}">
                                    @csrf
                                    <button class="px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
                                        Reopen
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

    </div>

</x-app-layout>

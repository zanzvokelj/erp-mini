<div class="h-16 border-b border-gray-200 bg-slate-150 flex items-center justify-between py-6 px-6">
    @php($user = auth()->user())

    <!-- Search -->
    <div class="w-80">
        <form method="GET" action="/products">
        <input
            type="text"
            placeholder="Search..."
            class="w-full bg-slate-50 border border-gray-200 rounded-md px-3 py-2 text-sm
           focus:outline-none"
        />
        </form>
    </div>

    <div class="flex-1 px-8 text-center">
        <div class="inline-flex flex-col items-center">
            <div class="text-[10px] font-semibold uppercase tracking-[0.32em] text-slate-400">
                Workspace
            </div>
            <div class="text-3xl font-semibold tracking-[-0.04em] text-slate-900">
                {{ $user?->company?->name ?? 'No company' }}
            </div>
            <div class="mt-1 h-px w-20 bg-gradient-to-r from-transparent via-slate-300 to-transparent"></div>
        </div>
    </div>

    <!-- User menu -->
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 flex items-center justify-center text-xs font-semibold text-slate-700">
                {{ $user?->name }}
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-sm text-slate-500 hover:text-slate-800 transition-colors">
                Logout
            </button>
        </form>

    </div>

</div>

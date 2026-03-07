<div class="h-16 border-b border-gray-200 flex items-center justify-between py-6 px-6">

    <!-- Search -->
    <div class="w-96">
        <form method="GET" action="/products">
        <input
            type="text"
            placeholder="Search..."
            class="w-full bg-slate-50 border border-gray-200 rounded-md px-3 py-2 text-sm
           focus:outline-none"
        />
        </form>
    </div>

    <!-- User menu -->
    <div class="flex items-center gap-4">

        <div class="flex items-center gap-3">

            <div class="rounded-xl p-2 bg-slate-200 flex items-center justify-center text-xs font-semibold text-gray-700">
                {{ auth()->user()->name }}
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-sm text-gray-500 hover:text-gray-800">
                Logout
            </button>
        </form>

    </div>

</div>

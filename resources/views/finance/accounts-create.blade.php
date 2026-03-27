<x-app-layout>

    <div class="p-8 max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">Create Account</h1>
            <p class="text-sm text-gray-500 mt-1">Add a new chart of accounts record.</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <form method="POST" action="{{ route('finance.accounts.store') }}">
                @include('finance.accounts-form', ['submitLabel' => 'Create Account'])
            </form>
        </div>
    </div>

</x-app-layout>

<x-app-layout>

    <div class="p-8 max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold">Edit Account</h1>
            <p class="text-sm text-gray-500 mt-1">Update account metadata and activation state.</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <form method="POST" action="{{ route('finance.accounts.update', $account) }}">
                @csrf
                @method('PUT')
                @include('finance.accounts-form', ['submitLabel' => 'Save Changes'])
            </form>
        </div>
    </div>

</x-app-layout>

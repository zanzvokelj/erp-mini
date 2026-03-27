<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-gray-800">
            Add Customer
        </h2>
    </x-slot>

    <div class="max-w-3xl space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-semibold text-gray-800">New Customer</h1>
                <p class="text-sm text-gray-500 mt-1">Create a retail or wholesale customer record.</p>
            </div>

            <a
                href="{{ route('customers.index') }}"
                class="px-4 py-2 text-sm border border-gray-200 rounded-md bg-white hover:bg-gray-50"
            >
                Back
            </a>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <form method="POST" action="{{ route('customers.store') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Name
                    </label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        required
                        class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                    />
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                            Type
                        </label>
                        <select
                            id="type"
                            name="type"
                            required
                            class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                        >
                            <option value="retail" {{ old('type', 'retail') === 'retail' ? 'selected' : '' }}>
                                Retail
                            </option>
                            <option value="wholesale" {{ old('type') === 'wholesale' ? 'selected' : '' }}>
                                Wholesale
                            </option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="discount_percent" class="block text-sm font-medium text-gray-700 mb-1">
                            Discount Percent
                        </label>
                        <input
                            id="discount_percent"
                            name="discount_percent"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value="{{ old('discount_percent', '0.00') }}"
                            required
                            class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                        />
                        @error('discount_percent')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="credit_limit" class="block text-sm font-medium text-gray-700 mb-1">
                            Credit Limit
                        </label>
                        <input
                            id="credit_limit"
                            name="credit_limit"
                            type="number"
                            min="0"
                            step="0.01"
                            value="{{ old('credit_limit', '0.00') }}"
                            required
                            class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm bg-slate-50"
                        />
                        @error('credit_limit')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        Create Customer
                    </button>

                    <a
                        href="{{ route('customers.index') }}"
                        class="px-4 py-2 text-sm border border-gray-200 rounded-md bg-white hover:bg-gray-50"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>

    </div>

</x-app-layout>

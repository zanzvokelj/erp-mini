@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm text-gray-500 mb-1">Code</label>
        <input
            type="text"
            name="code"
            value="{{ old('code', $account->code ?? '') }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2"
            required
        >
    </div>

    <div>
        <label class="block text-sm text-gray-500 mb-1">Name</label>
        <input
            type="text"
            name="name"
            value="{{ old('name', $account->name ?? '') }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2"
            required
        >
    </div>

    <div>
        <label class="block text-sm text-gray-500 mb-1">Type</label>
        <select name="type" class="w-full border border-gray-200 rounded-lg px-3 py-2" required>
            @foreach($types as $type)
                <option value="{{ $type }}" {{ old('type', $account->type ?? '') === $type ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm text-gray-500 mb-1">Category</label>
        <select name="category" class="w-full border border-gray-200 rounded-lg px-3 py-2">
            <option value="">None</option>
            @foreach($categories as $category)
                <option value="{{ $category }}" {{ old('category', $account->category ?? '') === $category ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $category)) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm text-gray-500 mb-1">Subtype</label>
        <input
            type="text"
            name="subtype"
            value="{{ old('subtype', $account->subtype ?? '') }}"
            class="w-full border border-gray-200 rounded-lg px-3 py-2"
        >
    </div>

    <div class="flex items-center gap-2 pt-7">
        <input
            id="is_active"
            type="checkbox"
            name="is_active"
            value="1"
            {{ old('is_active', ($account->is_active ?? true)) ? 'checked' : '' }}
        >
        <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>
</div>

@if($errors->any())
    <div class="mt-4 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
        {{ $errors->first() }}
    </div>
@endif

<div class="mt-6 flex items-center gap-3">
    <button class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        {{ $submitLabel }}
    </button>

    <a
        href="{{ route('finance.accounts.index') }}"
        class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
    >
        Cancel
    </a>
</div>

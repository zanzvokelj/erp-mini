
@props(['href'])

@php
    $active = request()->is(ltrim($href, '/'));
@endphp

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'flex items-center px-3 py-2 rounded-md transition ' .
        ($active
            ? 'bg-slate-100 border border-gray-200 shadow-sm text-gray-900 font-medium'
            : 'text-gray-600 hover:bg-slate-50')
    ]) }}>
    {{ $slot }}
</a>

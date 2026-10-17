@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-xl bg-brand-600 px-3.5 py-2 text-sm font-semibold leading-5 text-white shadow-sm shadow-brand-200 focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-xl px-3.5 py-2 text-sm font-medium leading-5 text-slate-500 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:bg-brand-50 focus:text-brand-700 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>

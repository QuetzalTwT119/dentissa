@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-brand-600 text-start text-sm font-semibold text-brand-700 bg-brand-50/70 rounded-r-xl focus:outline-none focus:text-brand-800 focus:bg-brand-100 focus:border-brand-700 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2.5 border-l-4 border-transparent text-start text-sm font-medium text-[#5c4f53] hover:text-brand-700 hover:bg-[#faf7f4] hover:border-brand-300 rounded-r-xl focus:outline-none focus:text-brand-800 focus:bg-brand-50 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>

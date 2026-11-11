@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex min-h-11 items-center rounded-full bg-brand-50 px-4 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200/70 transition-colors duration-150 hover:bg-brand-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400'
            : 'inline-flex min-h-11 items-center rounded-full px-4 text-sm font-semibold text-ink-soft transition-colors duration-150 hover:bg-white hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>

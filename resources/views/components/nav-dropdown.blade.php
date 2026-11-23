@props(['active' => false])

@php
$classes = $active
            ? 'inline-flex min-h-11 items-center rounded-full bg-brand-50 px-3 text-sm xl:px-4 font-semibold text-brand-700 ring-1 ring-inset ring-brand-200/70 transition-colors duration-150 hover:bg-brand-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400'
            : 'inline-flex min-h-11 items-center rounded-full px-3 text-sm font-semibold xl:px-4 text-ink-soft transition-colors duration-150 hover:bg-white hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300';
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <button type="button" @click="open = ! open" class="{{ $classes }}">
        {{ $label }}

        <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-out duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute start-0 z-50 mt-2 w-56 rounded-2xl border border-line shadow-lift ltr:origin-top-left rtl:origin-top-right"
            style="display: none;"
            @click="open = false">
        <div class="rounded-2xl bg-white p-1.5">
            {{ $slot }}
        </div>
    </div>
</div>

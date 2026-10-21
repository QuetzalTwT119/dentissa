@props(['active' => false])

@php
$classes = $active
            ? 'inline-flex min-h-11 items-center rounded-full bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2'
            : 'inline-flex min-h-11 items-center rounded-full px-4 text-sm font-semibold text-[#5c4f53] transition hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-300';
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <button type="button" @click="open = ! open" class="{{ $classes }}">
        {{ $label }}

        <svg class="ms-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute start-0 z-50 mt-2 w-56 rounded-2xl border border-[#e9dfdb] shadow-xl ltr:origin-top-left rtl:origin-top-right"
            style="display: none;"
            @click="open = false">
        <div class="rounded-2xl bg-white py-1.5 ring-1 ring-black/5">
            {{ $slot }}
        </div>
    </div>
</div>

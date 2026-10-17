@props(['active' => false])

@php
$classes = $active
            ? 'inline-flex items-center rounded-xl bg-brand-50 px-3 py-2 text-sm font-semibold leading-5 text-brand-700 shadow-sm ring-1 ring-brand-100 focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-xl px-3 py-2 text-sm font-medium leading-5 text-slate-500 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:bg-brand-50 focus:text-brand-700 transition duration-150 ease-in-out';
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
            class="absolute z-50 mt-2 w-52 rounded-2xl shadow-xl shadow-slate-900/10 ltr:origin-top-left rtl:origin-top-right start-0"
            style="display: none;"
            @click="open = false">
        <div class="rounded-2xl border border-brand-100 bg-white py-1.5">
            {{ $slot }}
        </div>
    </div>
</div>

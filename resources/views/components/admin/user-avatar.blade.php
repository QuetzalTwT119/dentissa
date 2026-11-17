@props([
    'name',
    'size' => 'h-10 w-10',
])

@php
    $initials = \App\Support\Iniciales::deTexto($name);
@endphp

<div {{ $attributes->merge(['class' => "flex {$size} shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-peach-100 to-brand-100 text-xs font-semibold text-brand-800 ring-1 ring-inset ring-brand-200/70"]) }}>
    {{ $initials }}
</div>

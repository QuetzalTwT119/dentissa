@props([
    'name',
    'size' => 'h-10 w-10',
])

@php
    $parts = preg_split('/\s+/', trim($name));
    $i1 = substr($parts[0] ?? '', 0, 1);
    $i2 = substr($parts[1] ?? '', 0, 1);
    $initials = strtoupper($i1 . $i2) ?: 'U';
@endphp

<div {{ $attributes->merge(['class' => "flex {$size} shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-50 to-peach-100 text-xs font-bold text-brand-700 border border-brand-200/80 shadow-xs"]) }}>
    {{ $initials }}
</div>

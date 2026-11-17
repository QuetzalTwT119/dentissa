@props([
    // success | error | warning | info
    'type' => 'info',
    'message' => null,
])

@php
    // Los errores interrumpen al lector de pantalla (role="alert");
    // los demás avisos se anuncian sin interrumpir (role="status").
    $estilos = [
        'success' => ['bg-sage-50 text-sage-700 ring-sage-500/20', 'status', 'M5 13l4 4L19 7'],
        'error' => ['bg-rose-50 text-rose-800 ring-rose-300/50', 'alert', 'M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z'],
        'warning' => ['bg-amber-50 text-amber-900 ring-amber-300/50', 'status', 'M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z'],
        'info' => ['bg-peach-50 text-ink ring-peach-200', 'status', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
    ];
    [$clases, $rol, $icono] = $estilos[$type] ?? $estilos['info'];
@endphp

<div role="{{ $rol }}" {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-2xl px-4 py-3.5 text-callout font-medium ring-1 ring-inset {$clases}"]) }}>
    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono }}" />
    </svg>
    <div class="min-w-0 flex-1 break-words">{{ $message ?? $slot }}</div>
</div>

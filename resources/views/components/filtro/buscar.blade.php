{{--
    Buscador de filtro (ver components/filtro/barra). Actualiza la lista al
    escribir sin recargar la página (app.js, "Buscador global"); Enter también
    filtra. Sin botón "Buscar".
--}}
@props(['name' => 'buscar', 'etiqueta', 'placeholder' => 'Buscar…', 'valor' => null, 'id' => null])

@php
    $actual = (string) ($valor ?? request($name, ''));
@endphp

<div class="relative w-full sm:w-72">
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted" aria-hidden="true">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4" stroke-linecap="round"/></svg>
    </span>
    <input type="search" enterkeyhint="search" name="{{ $name }}" id="{{ $id ?? 'filtro_'.$name }}" value="{{ $actual }}" maxlength="100"
           placeholder="{{ $placeholder }}" aria-label="{{ $etiqueta }}"
           data-filtro-buscar
           {{ $attributes->class([
               'min-h-11 w-full rounded-xl border pl-9 pr-3 text-sm text-ink shadow-2xs transition placeholder:text-muted focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200',
               'border-field bg-white hover:border-brand-300' => $actual === '',
               'border-brand-400 bg-brand-50' => $actual !== '',
           ]) }}>
</div>

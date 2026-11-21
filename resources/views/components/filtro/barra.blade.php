{{--
    Filtros globales del sistema (20/11/2026). Antes cada pantalla tenía su
    propio diseño (con y sin etiquetas, botones "Filtrar"/"Buscar", "Limpiar"
    en distintos lugares). Ahora todas usan estas piezas:

        <x-filtro.barra :action="route('admin.usuarios.index')" :campos="['rol', 'activo']">
            <x-filtro.select name="rol" etiqueta="Filtrar por rol" :opciones="[...]" todos="Todos los roles" />
            <x-filtro.buscar name="buscar" etiqueta="Buscar pacientes" placeholder="…" />
            <x-filtro.fecha name="desde" etiqueta="Desde" />
        </x-filtro.barra>

    Reglas: van en la barra de la lista (slot "filtros" de x-pagination-summary),
    se aplican solas al cambiar (sin botón), un filtro activo se resalta y
    "Limpiar filtros" aparece solo si hay algo filtrado.

    Props:
      - action: URL de la lista.
      - campos: nombres de los filtros (para saber si hay algo activo).
      - conservar: otros parámetros que deben mantenerse al filtrar (p. ej. fecha).
      - resultados: selector de la zona que el buscador actualiza sin recargar
        (por defecto la tabla, el conteo y la paginación).
--}}
@props(['action', 'campos' => [], 'conservar' => [], 'resultados' => null])

@php
    $activos = collect($campos)->contains(fn ($campo) => filled(request($campo)));
    $mantener = collect(['per_page'])->merge($conservar)
        ->filter(fn ($campo) => filled(request($campo)))
        ->mapWithKeys(fn ($campo) => [$campo => request($campo)]);
@endphp

<form method="GET" action="{{ $action }}" role="search" data-filtro-barra data-filtro-campos='@json(array_values($campos))'
      @if ($resultados) data-filtro-resultados="{{ $resultados }}" @endif
      {{-- Sin campos vacíos en la dirección (?accion=x en vez de ?desde=&hasta=&accion=x). --}}
      onsubmit="[...this.elements].forEach((c) => { if (c.name && c.value === '') c.disabled = true; })"
      {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    @foreach ($mantener as $nombre => $valor)
        <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
    @endforeach

    {{ $slot }}

    {{-- Enter en el buscador envía el formulario; el botón no se ve. --}}
    <button type="submit" class="sr-only">Aplicar filtros</button>

    {{-- Siempre presente (oculto sin filtros) para que el buscador lo muestre al escribir. --}}
    <a href="{{ $action }}{{ $mantener->isNotEmpty() ? '?'.http_build_query($mantener->all()) : '' }}"
           data-filtro-limpiar @class([
               'inline-flex min-h-11 items-center gap-1 rounded-full px-3 text-sm font-semibold text-muted transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300',
               'hidden' => ! $activos,
           ])>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
        Limpiar filtros
    </a>
</form>

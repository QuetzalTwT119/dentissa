{{--
    Lista desplegable de filtro (ver components/filtro/barra). Se aplica al
    cambiar. "todos" es la primera opción (sin filtro). Activo = resaltado.
    opciones: [valor => etiqueta]. La etiqueta visible es la opción elegida;
    "etiqueta" se anuncia a lectores de pantalla.
--}}
@props(['name', 'etiqueta', 'opciones' => [], 'todos' => 'Todos', 'valor' => null, 'id' => null])

@php
    $actual = (string) ($valor ?? request($name, ''));
    $activo = $actual !== '';
@endphp

<select name="{{ $name }}" id="{{ $id ?? 'filtro_'.$name }}" aria-label="{{ $etiqueta }}" onchange="this.form.requestSubmit()"
        {{ $attributes->class([
            'min-h-11 rounded-xl border pl-3 text-sm shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200',
            'border-field bg-white text-ink hover:border-brand-300' => ! $activo,
            'border-brand-400 bg-brand-50 font-semibold text-brand-800' => $activo,
        ]) }}>
    <option value="">{{ $todos }}</option>
    @foreach ($opciones as $valorOpcion => $texto)
        <option value="{{ $valorOpcion }}" @selected($actual === (string) $valorOpcion)>{{ $texto }}</option>
    @endforeach
</select>

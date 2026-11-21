{{--
    Fecha de filtro (ver components/filtro/barra). La etiqueta ("Desde",
    "Hasta") va dentro de la caja. Se aplica 700 ms después de cambiar y solo
    con la fecha completa: al escribir con teclado el año pasa por 0002, 0020…
--}}
@props(['name', 'etiqueta', 'valor' => null, 'id' => null, 'max' => null, 'min' => null])

@php
    $actual = (string) ($valor ?? request($name, ''));
    $idCampo = $id ?? 'filtro_'.$name;
@endphp

<label for="{{ $idCampo }}" @class([
    'inline-flex min-h-11 items-center gap-2 rounded-xl border pl-3 pr-1 shadow-2xs transition focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-200',
    'border-field bg-white hover:border-brand-300' => $actual === '',
    'border-brand-400 bg-brand-50' => $actual !== '',
])>
    <span class="text-xs font-semibold uppercase tracking-label text-muted">{{ $etiqueta }}</span>
    <input type="date" name="{{ $name }}" id="{{ $idCampo }}" value="{{ $actual }}"
           @if ($max) max="{{ $max }}" @endif @if ($min) min="{{ $min }}" @endif
           x-data="{ espera: null }"
           x-on:change="clearTimeout(espera); espera = setTimeout(() => { if (! $el.value || $el.value >= '2000') $el.form.requestSubmit(); }, 700)"
           {{ $attributes->merge(['class' => 'min-h-10 border-0 bg-transparent py-1 pl-0 pr-2 text-sm text-ink focus:outline-none focus:ring-0']) }}>
</label>

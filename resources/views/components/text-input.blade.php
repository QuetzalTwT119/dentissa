@props(['disabled' => false])

@php
    // Marca el campo como inválido si Laravel devolvió un error para él.
    // "dias[0][hora]" se traduce a "dias.0.hora", que es como lo guarda $errors.
    $nombre = $attributes->get('name');
    $clave = $nombre ? trim(str_replace(['[', ']'], ['.', ''], $nombre), '.') : null;
    $invalido = $clave && isset($errors) && $errors->has($clave);
@endphp

<input @disabled($disabled) @if ($invalido) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'min-h-11 rounded-xl border-field bg-white text-sm text-ink shadow-2xs placeholder:text-muted transition-[border-color,box-shadow] duration-150 focus:border-brand-400 focus:ring-4 focus:ring-brand-500/15 disabled:cursor-not-allowed disabled:bg-shell/60 disabled:text-muted']) }}>

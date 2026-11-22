{{--
    Lista desplegable global de formularios (21/11/2026). Mismo estilo que
    x-text-input (antes 16 <select> hechos a mano). Las opciones van en el slot.
    Para filtros de listas usa <x-filtro.select> (se aplica al cambiar).
    Uso: <x-select id="rol" name="rol" class="mt-1.5"><option …></x-select>
--}}
@php
    $nombre = $attributes->get('name');
    $clave = $nombre ? trim(str_replace(['[', ']'], ['.', ''], $nombre), '.') : null;
    $invalido = $clave && isset($errors) && $errors->has($clave);
@endphp

<select @if ($invalido) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'block w-full min-h-11 rounded-xl border-field bg-white pl-3.5 text-sm text-ink shadow-2xs transition-[border-color,box-shadow] duration-150 focus:border-brand-400 focus:ring-4 focus:ring-brand-500/15 disabled:cursor-not-allowed disabled:bg-shell/60 disabled:text-muted']) }}>{{ $slot }}</select>

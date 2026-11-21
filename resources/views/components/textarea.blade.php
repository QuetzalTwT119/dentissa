{{--
    Campo de texto largo global (21/11/2026). Mismo estilo que x-text-input
    (antes había 18 <textarea> hechos a mano con bordes y focos distintos).
    Uso: <x-textarea id="notas" name="notas" rows="3" class="mt-1.5">{{ old('notas') }}</x-textarea>
--}}
@php
    $nombre = $attributes->get('name');
    $clave = $nombre ? trim(str_replace(['[', ']'], ['.', ''], $nombre), '.') : null;
    $invalido = $clave && isset($errors) && $errors->has($clave);
@endphp

<textarea @if ($invalido) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'block w-full min-h-11 rounded-xl border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs placeholder:text-muted transition-[border-color,box-shadow] duration-150 focus:border-brand-400 focus:ring-4 focus:ring-brand-500/15 disabled:cursor-not-allowed disabled:bg-shell/60 disabled:text-muted']) }}>{{ $slot }}</textarea>

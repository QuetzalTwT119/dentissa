{{--
    "Nueva contraseña" + "Confirmar contraseña" (22/11/2026), igual en todo el
    sistema: los dos con 👁 para ver lo escrito, los requisitos a la vista y un
    aviso en vivo de si coinciden (antes solo al guardar: "no coincide").

    <x-contrasena-nueva prefijo="update_password_" :errores="$errors->updatePassword"
                        ayuda="Déjalo en blanco para mantener la contraseña actual." />

    Props:
      - prefijo: antepuesto a los id (los name siempre son password y password_confirmation).
      - errores: bolsa de errores (por omisión, la general).
      - requerida: si es obligatoria (crear usuario, restablecer).
      - etiqueta: "Nueva contraseña" por omisión.
      - ayuda: texto bajo la etiqueta.
      - requisitos: muestra los requisitos (por omisión sí).
--}}
@props([
    'prefijo' => '',
    'errores' => null,
    'requerida' => false,
    'etiqueta' => 'Nueva contraseña',
    'ayuda' => null,
    'requisitos' => true,
])

@php
    $errores ??= $errors;
@endphp

<div x-data="{ nueva: '', confirmar: '' }" {{ $attributes }} data-contrasena-nueva>
    @if ($ayuda)
        <p class="mb-3 text-sm text-muted">{{ $ayuda }}</p>
    @endif
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label :required="$requerida" for="{{ $prefijo }}password" :value="$etiqueta" />
        <x-campo-contrasena id="{{ $prefijo }}password" name="password" x-model="nueva" :required="$requerida" />
        @if ($requisitos)
            <p class="mt-1.5 text-xs text-muted">Mínimo 10 caracteres, con mayúsculas, minúsculas y números.</p>
        @endif
        <x-input-error :messages="$errores->get('password')" class="mt-2" />
    </div>

    <div>
        <x-input-label :required="$requerida" for="{{ $prefijo }}password_confirmation" value="Confirmar contraseña" />
        <x-campo-contrasena id="{{ $prefijo }}password_confirmation" name="password_confirmation" x-model="confirmar" :required="$requerida" />
        {{-- Aviso en vivo: solo cuando ya escribió en "Confirmar". --}}
        <p class="mt-1.5 min-h-[1rem] text-xs font-medium" aria-live="polite" data-aviso-coincide>
            <span x-show="confirmar !== '' && nueva === confirmar" x-cloak class="text-sage-700">✓ Coinciden</span>
            <span x-show="confirmar !== '' && nueva !== confirmar" x-cloak class="text-amber-700">Todavía no coinciden</span>
        </p>
        <x-input-error :messages="$errores->get('password_confirmation')" class="mt-2" />
    </div>
    </div>
</div>

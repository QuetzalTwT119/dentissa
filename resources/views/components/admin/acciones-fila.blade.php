{{--
    Acciones de una fila (Editar / Eliminar) iguales en TODAS las listas del panel.
    Antes cada lista armaba sus íconos con tamaños y estilos distintos.

    <x-admin.acciones-fila :editar="route(...)" :eliminar="route(...)"
                           que="el tratamiento" :nombre="$t->nombre">
        (acciones extra opcionales, p. ej. Aprobar)
    </x-admin.acciones-fila>

    bloqueoEliminar (22/11/2026): motivo por el que NO se puede eliminar (p. ej.
    citas activas). En lugar de la papelera sale ⚠ con el motivo; antes salía
    la papelera y el servidor rechazaba la eliminación.
--}}
@props(['editar' => null, 'eliminar' => null, 'que' => 'el registro', 'nombre' => null, 'confirmarTitulo' => null, 'bloqueoEliminar' => null])

@php
    $quien = trim($que.($nombre ? ' «'.$nombre.'»' : ''));
    $boton = 'grid h-11 w-11 place-items-center rounded-full transition-colors duration-150 focus:outline-none focus-visible:ring-2 lg:h-9 lg:w-9';
@endphp

{{-- Siempre a la derecha (22/11/2026; antes centrado en PC): con un botón con palabra
     ("Ocultar", "Aprobar") el grupo cambia de ancho y, centrado, Editar y Eliminar
     quedaban en otro lugar en cada fila. Así siempre están en la misma columna. El
     encabezado "Acciones" también va a la derecha. --}}
<div {{ $attributes->merge(['class' => 'flex items-center justify-end gap-1']) }}>
    {{ $slot }}

    @if ($editar)
        <a href="{{ $editar }}" title="Editar" aria-label="Editar {{ $quien }}"
           class="{{ $boton }} text-brand-600 hover:bg-brand-50 hover:text-brand-800 focus-visible:ring-brand-300">
            <x-icon name="edit" />
        </a>
    @endif

    @if ($eliminar && $bloqueoEliminar)
        {{-- La papelera apagada con su ⚠ (antes un ⚠ suelto en su lugar: no se sabía qué era). --}}
        <span class="grid h-11 w-11 place-items-center lg:h-9 lg:w-9" data-no-eliminable>
            <x-ayuda tono="aviso" etiqueta="No se puede eliminar">
                <x-slot:icono>
                    <span class="relative grid place-items-center">
                        <x-icon name="trash" class="text-muted opacity-50" />
                        <svg class="absolute -bottom-1 -right-1.5 h-3 w-3 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 3.3a1.7 1.7 0 0 1 3 0l6.3 11.2A1.7 1.7 0 0 1 16.3 17H3.7a1.7 1.7 0 0 1-1.5-2.5L8.5 3.3ZM10 7a.9.9 0 0 0-.9.9v3.4a.9.9 0 1 0 1.8 0V7.9A.9.9 0 0 0 10 7Zm0 7.6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                    </span>
                </x-slot:icono>
                {{ $bloqueoEliminar }}
            </x-ayuda>
        </span>
    @elseif ($eliminar)
        <form method="POST" action="{{ $eliminar }}" class="inline-flex"
              data-confirmar="Esta acción no se puede deshacer."
              data-confirmar-titulo="{{ $confirmarTitulo ?? '¿Eliminar '.$quien.'?' }}"
              data-confirmar-boton="Sí, eliminar" data-confirmar-tono="peligro">
            @csrf
            @method('delete')
            <button type="submit" title="Eliminar" aria-label="Eliminar {{ $quien }}"
                    class="{{ $boton }} text-rose-600 hover:bg-rose-50 hover:text-rose-800 focus-visible:ring-rose-300">
                <x-icon name="trash" />
            </button>
        </form>
    @endif
</div>

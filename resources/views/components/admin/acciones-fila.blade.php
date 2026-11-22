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

{{-- En PC el grupo va centrado bajo "Acciones" (21/11/2026): alineado a la derecha, el
     encabezado y los botones redondos no coincidían a la vista. --}}
<div {{ $attributes->merge(['class' => 'flex items-center justify-end gap-1 lg:justify-center']) }}>
    {{ $slot }}

    @if ($editar)
        <a href="{{ $editar }}" title="Editar" aria-label="Editar {{ $quien }}"
           class="{{ $boton }} text-brand-600 hover:bg-brand-50 hover:text-brand-800 focus-visible:ring-brand-300">
            <x-icon name="edit" />
        </a>
    @endif

    @if ($eliminar && $bloqueoEliminar)
        <span class="grid h-11 w-11 place-items-center lg:h-9 lg:w-9" data-no-eliminable>
            <x-ayuda tono="aviso" etiqueta="No se puede eliminar">{{ $bloqueoEliminar }}</x-ayuda>
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

{{--
    Acciones de una fila (Editar / Eliminar) iguales en TODAS las listas del panel.
    Antes cada lista armaba sus íconos con tamaños y estilos distintos.

    <x-admin.acciones-fila :editar="route(...)" :eliminar="route(...)"
                           que="el tratamiento" :nombre="$t->nombre">
        (acciones extra opcionales, p. ej. Aprobar)
    </x-admin.acciones-fila>
--}}
@props(['editar' => null, 'eliminar' => null, 'que' => 'el registro', 'nombre' => null, 'confirmarTitulo' => null])

@php
    $quien = trim($que.($nombre ? ' «'.$nombre.'»' : ''));
    $boton = 'grid h-11 w-11 place-items-center rounded-full transition-colors duration-150 focus:outline-none focus-visible:ring-2 lg:h-9 lg:w-9';
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-end gap-1']) }}>
    {{ $slot }}

    @if ($editar)
        <a href="{{ $editar }}" title="Editar" aria-label="Editar {{ $quien }}"
           class="{{ $boton }} text-brand-600 hover:bg-brand-50 hover:text-brand-800 focus-visible:ring-brand-300">
            <x-icon name="edit" />
        </a>
    @endif

    @if ($eliminar)
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

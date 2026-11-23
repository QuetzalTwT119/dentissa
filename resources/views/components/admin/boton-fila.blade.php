{{--
    Botón de acción de una fila que CAMBIA algo (aprobar, rechazar, marcar
    leído, mover…), 21/11/2026. Mismo tamaño que Editar/Eliminar de
    x-admin.acciones-fila y SIEMPRE pide confirmación con el diálogo del
    sistema (components/dialogo-confirmar): ningún cambio se hace de un clic.

    <x-admin.boton-fila :action="route('admin.testimonios.aprobar', $t)" icono="check" color="sage"
                        etiqueta="Aprobar testimonio de Ana" titulo="¿Aprobar y publicar este testimonio?"
                        mensaje="Se mostrará en la portada." boton="Sí, aprobar" />

    Props:
      - action, metodo (patch por omisión).
      - icono: nombre de x-icon; o el slot para un ícono propio.
      - etiqueta: texto para lectores de pantalla y tooltip.
      - titulo, mensaje, boton: textos del diálogo de confirmación.
      - tono: normal (por omisión) | peligro.
      - color: brand | sage | amber | rose | muted.
      Solo ícono, igual que Editar/Eliminar (22/11/2026). Se probó ícono + palabra y
      la columna de acciones se ensanchaba hasta salirse de la tabla (Testimonios) y
      mezclaba dos estilos; ahora cada acción usa un ícono que se entiende solo
      (ojo = publicar, ojo tachado = ocultar, pulgar = aprobar/rechazar, sobre =
      leído) y su nombre sale al pasar el mouse y en la confirmación.
--}}
@props([
    'action',
    'metodo' => 'patch',
    'icono' => null,
    'etiqueta',
    'titulo',
    'mensaje',
    'boton' => 'Sí, continuar',
    'tono' => 'normal',
    'color' => 'brand',
    'deshabilitado' => false,
])

@php
    $colores = [
        'brand' => 'text-brand-600 hover:bg-brand-50 hover:text-brand-800 focus-visible:ring-brand-300',
        'sage' => 'text-sage-700 hover:bg-sage-50 focus-visible:ring-sage-500/40',
        'amber' => 'text-amber-700 hover:bg-amber-50 focus-visible:ring-amber-300',
        'rose' => 'text-rose-600 hover:bg-rose-50 hover:text-rose-800 focus-visible:ring-rose-300',
        'muted' => 'text-muted hover:bg-cream hover:text-ink focus-visible:ring-brand-300',
    ];
@endphp

<form method="POST" action="{{ $action }}" class="inline-flex"
      data-confirmar="{{ $mensaje }}" data-confirmar-titulo="{{ $titulo }}"
      data-confirmar-boton="{{ $boton }}" data-confirmar-tono="{{ $tono }}">
    @csrf
    @if (strtolower($metodo) !== 'post')
        @method($metodo)
    @endif
    <button type="submit" title="{{ $etiqueta }}" aria-label="{{ $etiqueta }}" @disabled($deshabilitado)
            {{ $attributes->class([
                'grid h-11 w-11 place-items-center rounded-full transition-colors duration-150 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent lg:h-9 lg:w-9',
                $colores[$color] ?? $colores['brand'],
            ]) }}>
        @if ($icono)
            <x-icon :name="$icono" />
        @else
            {{ $slot }}
        @endif

    </button>
</form>

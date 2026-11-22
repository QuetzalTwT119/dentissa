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
      - texto: palabra visible junto al ícono (22/11/2026). Para acciones que el ícono
        solo no explica (Publicar, Ocultar, Aprobar, Rechazar…). Editar, eliminar y
        las flechas se entienden solos y quedan sin texto.
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
    'texto' => null,
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
                'rounded-full transition-colors duration-150 focus:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent',
        // Con texto: píldora con borde (se ve como botón); sin texto: círculo como Editar/Eliminar.
        'inline-flex min-h-11 items-center gap-1.5 whitespace-nowrap border border-line bg-white px-3 text-xs font-semibold lg:min-h-9' => filled($texto),
        'grid h-11 w-11 place-items-center lg:h-9 lg:w-9' => blank($texto),
                $colores[$color] ?? $colores['brand'],
            ]) }}>
        @if ($icono)
            <x-icon :name="$icono" />
        @else
            {{ $slot }}
        @endif
        @if (filled($texto))
            <span>{{ $texto }}</span>
        @endif
    </button>
</form>

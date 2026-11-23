{{--
    Interruptor global de estado (22/11/2026). Antes cada lista mostraba el
    estado distinto: Tratamientos con interruptor, Servicios/Casos/Galería/
    Preguntas con una etiqueta (había que entrar a Editar) y Usuarios con
    etiqueta + botón aparte. Ahora todo lo que se enciende y apaga usa esto.

    Se reconoce a simple vista como algo que se cambia; como todo cambio del
    panel, pide confirmación (data-confirmar, diálogo del sistema).

    <x-admin.interruptor-estado :action="route('admin.casos.estado', $caso)" :activo="$caso->activo" :nombre="$caso->titulo"
        desactivar="Dejará de mostrarse en la portada." activar="Se mostrará en la portada." />

    Props:
      - action, activo, nombre (para el título del diálogo y lectores de pantalla).
      - activar / desactivar: qué pasará (mensaje del diálogo).
      - textoActivo / textoInactivo: "Activo"/"Inactivo" por omisión (p. ej. "Visible"/"Oculta").
      - Slot "bloqueo": si se pasa, el interruptor sale apagado y bloqueado y
        en su lugar se muestra ese contenido (el motivo o el camino para resolverlo).
--}}
@props([
    'action',
    'activo' => false,
    'nombre',
    'activar' => 'Volverá a estar disponible.',
    'desactivar' => 'Dejará de estar disponible.',
    'textoActivo' => 'Activo',
    'textoInactivo' => 'Inactivo',
])

@if (isset($bloqueo) && $bloqueo->isNotEmpty())
    <div class="flex items-center gap-2.5" data-interruptor-bloqueado>
        <span aria-hidden="true" class="relative h-6 w-11 shrink-0 cursor-not-allowed rounded-full opacity-60 {{ $activo ? 'bg-sage-600' : 'bg-line' }}">
            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm {{ $activo ? 'translate-x-5' : '' }}"></span>
        </span>
        <span class="text-sm font-medium {{ $activo ? 'text-sage-700' : 'text-muted' }}">{{ $activo ? $textoActivo : $textoInactivo }}</span>
        {{ $bloqueo }}
    </div>
@else
    <form method="POST" action="{{ $action }}" data-interruptor-estado
          data-confirmar="{{ $activo ? $desactivar : $activar }}"
          data-confirmar-titulo="{{ $activo ? '¿Desactivar' : '¿Activar' }} «{{ $nombre }}»?"
          data-confirmar-boton="{{ $activo ? 'Sí, desactivar' : 'Sí, activar' }}"
          data-confirmar-tono="{{ $activo ? 'peligro' : 'normal' }}">
        @csrf
        @method('patch')
        <button type="submit" data-alternar-estado role="switch" aria-checked="{{ $activo ? 'true' : 'false' }}"
                title="{{ $activo ? 'Clic para desactivar' : 'Clic para activar' }}"
                aria-label="{{ $nombre }}: {{ $activo ? $textoActivo : $textoInactivo }}"
                class="group inline-flex min-h-11 items-center gap-2.5 rounded-full pr-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
            <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200 {{ $activo ? 'bg-sage-600' : 'bg-line group-hover:bg-peach-300' }}">
                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200 ease-out motion-reduce:transition-none {{ $activo ? 'translate-x-5' : '' }}"></span>
            </span>
            <span class="text-sm font-medium {{ $activo ? 'text-sage-700' : 'text-muted' }}">{{ $activo ? $textoActivo : $textoInactivo }}</span>
        </button>
    </form>
@endif

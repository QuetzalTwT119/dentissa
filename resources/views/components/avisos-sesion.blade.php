{{--
    Aviso del resultado de una acción ("Tratamiento «Valoración» creado.") para
    TODO el panel. Antes solo Citas y Notificaciones pintaban estos mensajes:
    en las demás pantallas las acciones se hacían en silencio.

    - Éxito: se va solo a los 6 s (o al cerrarlo).
    - Error: se queda hasta cerrarlo, porque pide que la persona haga algo.
    Abajo a la derecha, para no encimarse con los avisos de notificaciones nuevas
    (que salen arriba).
--}}
@php
    $avisos = array_filter([
        'success' => session('success'),
        'error' => session('error'),
    ]);
@endphp

@if ($avisos)
    <div data-avisos-sesion class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-stretch gap-2 sm:inset-x-auto sm:bottom-6 sm:right-6 sm:w-[min(26rem,calc(100vw-3rem))]">
        @foreach ($avisos as $tipo => $mensaje)
            <div x-data="{ visible: true }"
                 x-init="{{ $tipo === 'success' ? 'setTimeout(() => visible = false, 6000)' : '' }}"
                 x-show="visible"
                 x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="pointer-events-auto relative rounded-2xl bg-white shadow-lift"
                 data-aviso-sesion="{{ $tipo }}">
                <x-alert :type="$tipo" :message="$mensaje" class="pr-12" />
                <button type="button" x-on:click="visible = false"
                        class="absolute right-1.5 top-1.5 inline-flex h-9 w-9 items-center justify-center rounded-full text-current opacity-70 transition hover:bg-white/60 hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300"
                        aria-label="Cerrar aviso">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                </button>
            </div>
        @endforeach
    </div>
@endif

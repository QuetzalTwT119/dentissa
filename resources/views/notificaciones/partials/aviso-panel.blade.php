{{--
    Un aviso del panel de la campana. Se usa al cargar la página y también en la
    actualización en vivo (NotificacionSistema::htmlPanel), por eso no depende de
    variables del layout.

    - Por atender (prioritario sin atender): tarjeta con acción y "Ya lo atendí".
    - Informativo: renglón que abre el aviso y lo marca como leído.
    La figura es el avatar del paciente con una insignia de lo que pasó; los
    avisos del sistema usan un marco punteado porque no vienen de una persona.
--}}
@php
    [$icono, $tono] = $notificacion->insignia();
    $persona = $notificacion->persona();
    $porAtender = $notificacion->requiereAccion();
    $iconos = [
        'calendario' => '<path d="M8 3v3m8-3v3M4.5 9.5h15M6.5 5h11a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2h-11a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/>',
        'palomita' => '<path d="M5.5 12.5l4 4 9-9"/>',
        'tache' => '<path d="M7 7l10 10M17 7 7 17"/>',
        'ciclo' => '<path d="M17.5 9.2A6 6 0 0 0 6.6 9M6.5 14.8A6 6 0 0 0 17.4 15M6.4 5.8V9h3.2M17.6 18.2V15h-3.2"/>',
        'alerta' => '<path d="M12 8.5v5M12 17h.01M10.3 4.6 3.4 16.6a2 2 0 0 0 1.7 3h13.8a2 2 0 0 0 1.7-3L13.7 4.6a2 2 0 0 0-3.4 0z"/>',
        'nube' => '<path d="M7 18.5a4 4 0 0 1-.6-7.96A5.5 5.5 0 0 1 17 9.2a4.7 4.7 0 0 1 .3 9.3H7z"/>',
    ];
    $svg = fn (string $clase) => '<svg class="'.$clase.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$iconos[$icono].'</svg>';
@endphp

@php
    $figura = $persona
        ? '<span class="aviso-avatar avatar-tono-'.$notificacion->tonoAvatar().'" aria-hidden="true">'.e($notificacion->iniciales()).'<span class="aviso-insignia aviso-tono-'.$tono.'">'.$svg('h-3 w-3').'</span></span>'
        : '<span class="aviso-sistema aviso-tono-'.$tono.'" aria-hidden="true">'.$svg('h-5 w-5').'</span>';
    $tiempo = '<time class="shrink-0 text-xs text-muted" datetime="'.$notificacion->created_at->toIso8601String().'">'.e($notificacion->created_at->diffForHumans()).'</time>';
@endphp

@if ($porAtender)
    @php [$textoAccion, $urlAccion] = $notificacion->accionPrincipal(); @endphp
    <article data-notification-preview="{{ $notificacion->id }}" data-notification-task class="mx-3 mb-2 flex gap-3 rounded-2xl border border-amber-200 bg-amber-50/60 p-3">
        {!! $figura !!}
        <div class="min-w-0 flex-1">
            <p class="flex items-start justify-between gap-3">
                <span data-notification-preview-title class="text-sm font-semibold text-ink">{{ $notificacion->titulo }}</span>
                {!! $tiempo !!}
            </p>
            @if ($persona)
                <p class="text-xs font-medium text-ink-soft">{{ $persona }}</p>
            @endif
            <p data-notification-preview-message class="mt-0.5 text-sm leading-5 text-ink-soft">{{ $notificacion->mensaje }}</p>
            <div class="mt-2.5 flex flex-wrap gap-1.5">
                <a href="{{ $urlAccion }}" data-notification-read-url="{{ route('notificaciones.leida', $notificacion, false) }}" class="inline-flex min-h-9 items-center rounded-full bg-brand-600 px-3.5 text-sm font-semibold text-white transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 active:scale-[0.97]">{{ $textoAccion }}</a>
                @if ($telefono = $notificacion->telefonoPaciente())
                    <a href="tel:{{ $telefono }}" class="inline-flex min-h-9 items-center rounded-full border border-line bg-white px-3.5 text-sm font-semibold text-ink transition hover:border-peach-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 active:scale-[0.97]">Llamar</a>
                @endif
                <button type="button" data-notification-attend-url="{{ route('notificaciones.atendida', $notificacion, false) }}" class="inline-flex min-h-9 items-center rounded-full border border-line bg-white px-3.5 text-sm font-semibold text-ink-soft transition hover:border-peach-300 hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 active:scale-[0.97] disabled:opacity-60">Ya lo atendí</button>
            </div>
        </div>
    </article>
@else
    <a data-notification-preview="{{ $notificacion->id }}" data-notification-read-url="{{ route('notificaciones.leida', $notificacion, false) }}" href="{{ $notificacion->url ?: route('citas.index', [], false) }}" class="relative flex gap-3 px-4 py-3 transition hover:bg-cream focus:bg-cream focus:outline-none {{ $notificacion->leida_en ? 'aviso-leido' : '' }}">
        <span data-notification-preview-dot class="absolute left-1.5 top-1/2 h-1.5 w-1.5 -translate-y-1/2 rounded-full {{ $notificacion->leida_en ? 'hidden' : 'bg-brand-600' }}" aria-hidden="true"></span>
        {!! $figura !!}
        <span class="min-w-0 flex-1">
            <span class="flex items-start justify-between gap-3">
                <span data-notification-preview-title class="text-sm {{ $notificacion->leida_en ? 'font-medium text-ink-soft' : 'font-semibold text-ink' }}">{{ $notificacion->titulo }}</span>
                {!! $tiempo !!}
            </span>
            @if ($persona)
                <span class="block text-xs font-medium text-ink-soft">{{ $persona }}</span>
            @endif
            <span data-notification-preview-message class="mt-0.5 block text-sm leading-5 text-ink-soft">{{ $notificacion->mensaje }}</span>
            <span class="sr-only">{{ $notificacion->leida_en ? 'Notificación leída' : 'Notificación nueva' }}</span>
        </span>
    </a>
@endif

{{--
    Renglón de una cita en la lista. En escritorio es una fila de tabla; en
    celular, una tarjeta de tres renglones (clases .fila-cita en app.css).
    Espera $cita y, opcional, $resaltada (la cita que está en curso).
--}}
@php
    $estadoDot = [
        'agendada' => 'bg-amber-500',
        'confirmada' => 'bg-sky-500',
        'en_curso' => 'bg-brand-500',
        'completada' => 'bg-sage-500',
        'cancelada' => 'bg-muted',
        'no_asistio' => 'bg-rose-500',
    ];
    $inactiva = $cita->estado->esInactiva();
    $terminada = in_array($cita->estado->value, ['completada', 'cancelada', 'no_asistio'], true);
    $minutos = (int) $cita->duracion_estimada;
    $duracion = $minutos < 60 ? "{$minutos} min" : (intdiv($minutos, 60).' h'.($minutos % 60 ? ' '.($minutos % 60).' min' : ''));
@endphp

<div role="row" data-cita="{{ $cita->id }}"
     @class([
         'fila-cita border-t border-line/70 first:border-t-0 last:rounded-b-3xl',
         'fila-cita-terminada' => $terminada,
         'bg-brand-50/50 shadow-[inset_3px_0_0_#d75078]' => $resaltada ?? false,
     ])>
    <div role="cell" class="fila-cita-hora tabular-nums">
        <p class="text-headline font-semibold text-ink">{{ $cita->fecha_hora->format('H:i') }}</p>
        @if ($minutos > 0)
            <p class="text-xs text-muted">a {{ $cita->fecha_hora->copy()->addMinutes($minutos)->format('H:i') }}</p>
        @endif
    </div>

    {{-- 25/11/2026 (QA en producción): el nombre abre el expediente y el teléfono marca;
         antes la doctora tenía que ir a Pacientes y buscarlo durante la consulta. --}}
    @php
        $verExpediente = $cita->paciente && auth()->user()?->puede('pacientes');
        $telefonoMarcable = preg_replace('/[^\d+]/', '', (string) $cita->paciente?->telefono);
    @endphp
    <div role="cell" class="fila-cita-paciente min-w-0">
        <p @class(['font-semibold text-ink [overflow-wrap:anywhere]', 'line-through' => $inactiva])>
            @if ($verExpediente)
                <a href="{{ route('admin.pacientes.historial.index', $cita->paciente) }}" class="underline-offset-2 hover:text-brand-700 hover:underline" data-cita-expediente>{{ $cita->paciente->nombre_completo }}</a>
            @else
                {{ $cita->paciente?->nombre_completo ?? 'Paciente' }}
            @endif
        </p>
        <p class="text-xs text-muted tabular-nums [overflow-wrap:anywhere]">
            @if ($telefonoMarcable !== '')
                <a href="tel:{{ $telefonoMarcable }}" class="underline-offset-2 hover:text-brand-700 hover:underline" data-cita-llamar>{{ $cita->paciente->telefono }}</a>
            @else
                {{ $cita->paciente?->email ?: '—' }}
            @endif
        </p>
    </div>

    <div role="cell" class="fila-cita-tratamiento min-w-0">
        <p class="line-clamp-2 text-sm font-medium text-ink-soft" title="{{ $cita->tratamiento->nombre ?? '' }}">{{ $cita->tratamiento->nombre ?? '—' }}</p>
        <p class="text-xs text-muted">{{ $cita->servicio->nombre ?? '—' }}@if ($minutos > 0) · {{ $duracion }}@endif</p>
        <x-cita-oferta :cita="$cita" />
    </div>

    <div role="cell" class="fila-cita-estado">
        <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $cita->estado->claseInsignia() }}">
            <span class="h-1.5 w-1.5 rounded-full {{ $estadoDot[$cita->estado->value] ?? 'bg-muted' }}" aria-hidden="true"></span>
            {{ $cita->estado->etiqueta() }}
        </span>
        @if ($cita->asistencia_confirmada_en && ! $inactiva)
            <span class="mt-1 block text-xs font-medium text-sage-700" data-asistencia-confirmada>✓ Paciente confirmó asistencia</span>
        @endif
        @if ($cita->citaNueva)
            <span class="mt-1 block text-xs font-medium text-brand-700" data-reagendada-a>↻ Reagendada por el paciente → {{ $cita->citaNueva->fecha_hora->locale('es')->translatedFormat('j M, H:i') }}</span>
        @endif
    </div>

    <div role="cell" class="fila-cita-acciones">
        @include('citas.partials.acciones', ['cita' => $cita])
    </div>
</div>

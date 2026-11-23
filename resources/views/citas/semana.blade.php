@php
    $nombresDia = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $abrevDia = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    $nombresMes = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $mesesLargos = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $sabado = $lunes->copy()->addDays(5);
    $rango = $lunes->day.' '.$nombresMes[$lunes->month - 1].' – '.$sabado->day.' '.$nombresMes[$sabado->month - 1].' '.$sabado->year;
    $fechaLarga = fn ($fecha) => ucfirst($nombresDia[$fecha->dayOfWeek]).' '.$fecha->day.' de '.$mesesLargos[$fecha->month - 1];
    $hhmm = fn (int $minutos) => intdiv($minutos, 60).':'.str_pad((string) ($minutos % 60), 2, '0', STR_PAD_LEFT);
    $columnas = 'grid-template-columns: 2.5rem repeat('.$dias->count().', minmax(0, 1fr))';
    $estiloNivel = [
        'libre' => 'border-line bg-white',
        'con_citas' => 'border-brand-100 bg-brand-50',
        'lleno' => 'border-brand-100 bg-brand-100',
    ];
    $textoNivel = ['libre' => 'libre', 'con_citas' => 'con citas, aún hay lugar', 'lleno' => 'lleno'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Control de Agenda"
            title="Citas"
            subtitle="Qué tan llena está cada parte de la semana y dónde cabe otra cita."
        />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('citas.partials.selector-vista', ['vistaActual' => 'semana'])

            <section data-semana="{{ $lunes->toDateString() }}"
                     x-data="{ bloque: @js($ocupacion['seleccion']) }"
                     aria-labelledby="titulo-semana"
                     class="rounded-3xl border border-line bg-white p-4 shadow-soft sm:p-6">

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('citas.index', ['vista' => 'semana', 'semana' => $semanaAnterior]) }}"
                           aria-label="Semana anterior"
                           class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-ink transition hover:bg-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                        <a href="{{ route('citas.index', ['vista' => 'semana']) }}"
                           class="inline-flex h-11 items-center rounded-full border border-line px-4 text-sm font-semibold text-ink transition hover:bg-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">Hoy</a>
                        <a href="{{ route('citas.index', ['vista' => 'semana', 'semana' => $semanaSiguiente]) }}"
                           aria-label="Semana siguiente"
                           class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-ink transition hover:bg-cream focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <h2 id="titulo-semana" class="font-serif text-title-3 text-ink">{{ $rango }}</h2>
                    <a href="{{ route('citas.create') }}" class="btn-brand h-11 shrink-0 px-5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        <span>Agendar cita</span>
                    </a>
                </div>

                <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1.55fr)_minmax(0,1fr)] lg:gap-8">
                    {{-- Mapa: un recuadro por bloque de 2 horas; más color, más ocupado. --}}
                    <div>
                        <div class="mb-2 grid gap-2.5 text-center" style="{{ $columnas }}">
                            <span aria-hidden="true"></span>
                            @foreach ($dias as $dia)
                                <div data-dia="{{ $dia['fecha']->toDateString() }}"@if ($dia['esHoy']) data-hoy @endif>
                                    <p @class(['text-sm font-semibold', 'text-brand-700' => $dia['esHoy'], 'text-ink' => ! $dia['esHoy']])>
                                        {{ $dia['esHoy'] ? 'Hoy' : $abrevDia[$dia['fecha']->dayOfWeek] }} {{ $dia['fecha']->day }}
                                    </p>
                                    @php($activas = $dia['citas']->reject(fn ($cita) => $cita->estado->esInactiva())->count())
                                    @if ($dia['abierto'])
                                        <p class="text-xs text-muted">{{ $activas }} {{ $activas === 1 ? 'cita' : 'citas' }}</p>
                                    @elseif (isset($ocupacion['cerrados'][$dia['fecha']->toDateString()]))
                                        {{-- Día cerrado con citas: se pueden consultar desde aquí. --}}
                                        <button type="button" x-on:click="bloque = @js($dia['fecha']->toDateString().'|cerrado')"
                                                class="text-xs font-semibold text-brand-700 underline decoration-brand-300 underline-offset-2 hover:text-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                            Cerrado · {{ $dia['citas']->count() }} {{ $dia['citas']->count() === 1 ? 'cita' : 'citas' }}
                                        </button>
                                    @else
                                        <p class="text-xs text-muted">Cerrado</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div role="grid" aria-label="Ocupación de la semana por bloques de 2 horas" class="grid gap-2.5" style="{{ $columnas }}">
                            @foreach ($ocupacion['horas'] as $hora)
                                <div class="pt-1 pr-1.5 text-right text-xs tabular-nums text-muted">{{ $hora }}:00</div>
                                @foreach ($dias as $dia)
                                    @php($clave = $dia['fecha']->toDateString().'|'.$hora)
                                    @php($bloque = $ocupacion['bloques'][$clave] ?? null)
                                    @if ($bloque)
                                        <button type="button"
                                                x-on:click="bloque = @js($clave)"
                                                :aria-pressed="(bloque === @js($clave)).toString()"
                                                aria-pressed="{{ $clave === $ocupacion['seleccion'] ? 'true' : 'false' }}"
                                                aria-label="{{ $fechaLarga($dia['fecha']) }}, {{ $hhmm($bloque['inicio']) }} a {{ $hhmm($bloque['fin']) }}: {{ $textoNivel[$bloque['nivel']] }}"
                                                data-bloque="{{ $clave }}" data-nivel="{{ $bloque['nivel'] }}"
                                                @class([
                                                    'h-12 rounded-xl border transition-[box-shadow,transform] duration-150 ease-out active:scale-[0.96] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 [&[aria-pressed=true]]:ring-2 [&[aria-pressed=true]]:ring-brand-600',
                                                    $estiloNivel[$bloque['nivel']],
                                                    'opacity-40' => $bloque['pasado'],
                                                ])></button>
                                    @else
                                        <div aria-hidden="true"></div>
                                    @endif
                                @endforeach
                            @endforeach
                        </div>

                        <p class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-4 rounded border border-line bg-white" aria-hidden="true"></span>Libre</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-4 rounded border border-brand-100 bg-brand-50" aria-hidden="true"></span>Con citas, aún hay lugar</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-3 w-4 rounded bg-brand-100" aria-hidden="true"></span>Lleno</span>
                        </p>
                    </div>

                    {{-- Detalle del bloque elegido. Sin JavaScript se ve el bloque que el servidor eligió. --}}
                    <div class="lg:sticky lg:top-24">
                        @foreach ($ocupacion['bloques'] as $clave => $bloque)
                            @continue(! $bloque)
                            @php($activasBloque = $bloque['activas'])
                            <div x-show="bloque === @js($clave)" @if ($clave !== $ocupacion['seleccion']) style="display: none" @endif
                                 data-detalle="{{ $clave }}" aria-live="polite"
                                 class="rounded-2xl border border-line bg-cream/70 p-4 sm:p-5">
                                <h3 class="font-serif text-title-3 text-ink">{{ $fechaLarga($bloque['fecha']) }}</h3>
                                <p class="mt-1 text-sm text-muted">
                                    {{ $hhmm($bloque['inicio']) }} a {{ $hhmm($bloque['fin']) }} · {{ $activasBloque }} {{ $activasBloque === 1 ? 'cita' : 'citas' }}
                                    · Horario {{ $bloque['horario'] }}
                                </p>

                                @unless ($bloque['pasado'])
                                    <p class="mt-4 text-xs font-semibold uppercase tracking-label text-muted">Dónde cabe una cita</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @forelse ($bloque['huecos'] as $hueco)
                                            <a href="{{ route('citas.create', ['fecha' => $bloque['fecha']->toDateString()]) }}"
                                               class="inline-flex min-h-9 items-center rounded-full border border-dashed border-brand-300 bg-white px-3 text-sm font-semibold tabular-nums text-brand-700 transition-colors hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                               aria-label="Agendar el {{ $fechaLarga($bloque['fecha']) }} a las {{ $hueco }}">{{ $hueco }}</a>
                                        @empty
                                            <p class="text-sm text-muted">Ya no queda lugar en este bloque.</p>
                                        @endforelse
                                    </div>
                                @endunless

                                <p class="mt-4 text-xs font-semibold uppercase tracking-label text-muted">Citas</p>
                                <ul class="mt-2 space-y-2" role="list">
                                    @forelse ($bloque['citas'] as $cita)
                                        @php($empiezaAqui = $cita->fecha_hora->hour * 60 + $cita->fecha_hora->minute >= $bloque['inicio'])
                                        <li>
                                            <a href="{{ route('citas.edit', $cita) }}"
                                               @class([
                                                   'block rounded-xl border bg-white px-3 py-2.5 text-sm transition hover:border-brand-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400',
                                                   'border-line/70 opacity-70' => $cita->estado->esInactiva(),
                                                   'border-line' => ! $cita->estado->esInactiva(),
                                               ])>
                                                <span class="flex items-center justify-between gap-2">
                                                    <span class="font-semibold tabular-nums text-ink">{{ $cita->fecha_hora->format('H:i') }}</span>
                                                    <span class="rounded-full px-2 py-0.5 text-caption font-semibold {{ $cita->estado->claseInsignia() }}">{{ $cita->estado->etiqueta() }}</span>
                                                </span>
                                                <span @class(['mt-1 block font-medium text-ink [overflow-wrap:anywhere]', 'line-through' => $cita->estado->esInactiva()])>{{ $cita->paciente?->nombre_completo ?? 'Paciente' }}</span>
                                                <span class="block text-xs text-muted">{{ $cita->tratamiento?->nombre ?? 'Sin tratamiento' }}</span>
                                                <x-cita-oferta :cita="$cita" />
                                                {{-- Una cita larga aparece en varios bloques; la marca va solo donde empieza. --}}
                                                @if ($cita->asistencia_confirmada_en && ! $cita->estado->esInactiva())
                                                    <span class="mt-1 block text-xs font-medium text-sage-700" @if ($empiezaAqui) data-asistencia-confirmada @endif>✓ Confirmó asistencia</span>
                                                @endif
                                                @if ($cita->citaNueva && $empiezaAqui)
                                                    <span class="mt-1 block text-xs font-medium text-brand-700" data-reagendada-a>↻ Reagendada → {{ $cita->citaNueva->fecha_hora->locale('es')->translatedFormat('j M, H:i') }}</span>
                                                @endif
                                            </a>
                                        </li>
                                    @empty
                                        <li class="text-sm text-muted">Sin citas en este bloque.</li>
                                    @endforelse
                                </ul>
                            </div>
                        @endforeach

                        @foreach ($ocupacion['cerrados'] as $fecha => $citasCerrado)
                            @php($clave = $fecha.'|cerrado')
                            <div x-show="bloque === @js($clave)" style="display: none" data-detalle="{{ $clave }}" aria-live="polite"
                                 class="rounded-2xl border border-line bg-cream/70 p-4 sm:p-5">
                                <h3 class="font-serif text-title-3 text-ink">{{ $fechaLarga(\Illuminate\Support\Carbon::parse($fecha)) }}</h3>
                                <p class="mt-1 text-sm text-muted">El consultorio no abre este día, pero tiene {{ $citasCerrado->count() }} {{ $citasCerrado->count() === 1 ? 'cita registrada' : 'citas registradas' }}.</p>
                                <ul class="mt-4 space-y-2" role="list">
                                    @foreach ($citasCerrado as $cita)
                                        <li>
                                            <a href="{{ route('citas.edit', $cita) }}" class="block rounded-xl border border-line bg-white px-3 py-2.5 text-sm transition hover:border-brand-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                                <span class="flex items-center justify-between gap-2">
                                                    <span class="font-semibold tabular-nums text-ink">{{ $cita->fecha_hora->format('H:i') }}</span>
                                                    <span class="rounded-full px-2 py-0.5 text-caption font-semibold {{ $cita->estado->claseInsignia() }}">{{ $cita->estado->etiqueta() }}</span>
                                                </span>
                                                <span class="mt-1 block font-medium text-ink [overflow-wrap:anywhere]">{{ $cita->paciente?->nombre_completo ?? 'Paciente' }}</span>
                                                <span class="block text-xs text-muted">{{ $cita->tratamiento?->nombre ?? 'Sin tratamiento' }}</span>
                                                <x-cita-oferta :cita="$cita" />
                                                @if ($cita->asistencia_confirmada_en && ! $cita->estado->esInactiva())
                                                    <span class="mt-1 block text-xs font-medium text-sage-700" data-asistencia-confirmada>✓ Confirmó asistencia</span>
                                                @endif
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach

                        @if (! $ocupacion['seleccion'])
                            <p class="rounded-2xl border border-line bg-cream/70 p-5 text-sm text-muted">El consultorio no abre esta semana.</p>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

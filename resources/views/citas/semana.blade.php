@php
    $nombresDia = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $nombresMes = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $etiquetaDia = fn ($fecha) => ucfirst($nombresDia[$fecha->dayOfWeek]).' '.$fecha->day;
    $sabado = $lunes->copy()->addDays(5);
    $rango = $lunes->day.' '.$nombresMes[$lunes->month - 1].' – '.$sabado->day.' '.$nombresMes[$sabado->month - 1].' '.$sabado->year;
    // En celular se abre en "hoy" si cae en esta semana; si no, en el primer día.
    $indiceInicial = max(0, $dias->search(fn ($dia) => $dia['esHoy']) ?: 0);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Control de Agenda"
            title="Citas"
            subtitle="Agenda de la semana: quién viene, a qué hora y en qué estado está cada cita."
        />
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('citas.partials.selector-vista', ['vistaActual' => 'semana'])

            <section data-semana="{{ $lunes->toDateString() }}"
                     x-data="{ dia: {{ $indiceInicial }}, total: {{ $dias->count() }} }"
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

                {{-- Celular: flechas para cambiar de día (solo con JavaScript). --}}
                <div x-cloak x-show="total > 1" class="mt-5 flex items-center justify-between gap-2 lg:hidden">
                    <button type="button" x-on:click="dia = Math.max(0, dia - 1)" x-bind:disabled="dia === 0"
                            aria-label="Día anterior"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-ink disabled:opacity-40">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="text-sm text-muted" x-text="`Día ${dia + 1} de ${total}`"></span>
                    <button type="button" x-on:click="dia = Math.min(total - 1, dia + 1)" x-bind:disabled="dia === total - 1"
                            aria-label="Día siguiente"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-ink disabled:opacity-40">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <div class="mt-5" style="--dias: {{ $dias->count() }}">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[repeat(var(--dias),minmax(0,1fr))] lg:gap-3">
                        @foreach ($dias as $indice => $dia)
                            <div data-dia="{{ $dia['fecha']->toDateString() }}"@if ($dia['esHoy']) data-hoy @endif
                                 x-bind:class="dia === {{ $indice }} ? '' : 'max-lg:hidden'"
                                 @class([
                                     'min-w-0 rounded-2xl border p-3',
                                     'border-brand-300 bg-brand-50/40' => $dia['esHoy'],
                                     'border-line bg-cream/50' => ! $dia['esHoy'] && $dia['abierto'],
                                     'border-line/70 bg-shell/60' => ! $dia['abierto'],
                                 ])>
                                <div class="flex items-baseline justify-between gap-2 border-b border-line/70 pb-2">
                                    <h3 @class(['text-sm font-semibold', 'text-brand-700' => $dia['esHoy'], 'text-ink' => ! $dia['esHoy']])>
                                        {{ $etiquetaDia($dia['fecha']) }}
                                        @if ($dia['esHoy']) <span class="font-normal">· hoy</span> @endif
                                    </h3>
                                    <span class="text-xs text-muted">{{ $dia['abierto'] ? $dia['horario'] : 'Cerrado' }}</span>
                                </div>

                                <ul class="mt-2 space-y-2" role="list">
                                    @forelse ($dia['citas'] as $cita)
                                        <li>
                                            <a href="{{ route('citas.edit', $cita) }}"
                                               @class([
                                                   'block rounded-xl border bg-white p-2.5 text-sm transition hover:border-brand-300 hover:shadow-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400',
                                                   'border-line/70 opacity-70' => $cita->estado->esInactiva(),
                                                   'border-line' => ! $cita->estado->esInactiva(),
                                               ])>
                                                <span class="flex items-center justify-between gap-2">
                                                    <span class="font-semibold tabular-nums text-ink">{{ $cita->fecha_hora->format('H:i') }}</span>
                                                    <span class="rounded-full px-2 py-0.5 text-caption font-semibold {{ $cita->estado->claseInsignia() }}">{{ $cita->estado->etiqueta() }}</span>
                                                </span>
                                                <span @class(['mt-1 block truncate font-medium text-ink', 'line-through' => $cita->estado->esInactiva()])>{{ $cita->paciente?->nombre_completo ?? 'Paciente' }}</span>
                                                <span class="block truncate text-xs text-muted">{{ $cita->tratamiento?->nombre ?? 'Sin tratamiento' }}</span>
                                                @if ($cita->asistencia_confirmada_en && ! $cita->estado->esInactiva())
                                                    <span class="mt-1 block truncate text-xs font-medium text-sage-700" data-asistencia-confirmada>✓ Confirmó asistencia</span>
                                                @endif
                                            </a>
                                        </li>
                                    @empty
                                        <li class="py-3 text-center text-xs text-muted">{{ $dia['abierto'] ? 'Sin citas' : '—' }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>

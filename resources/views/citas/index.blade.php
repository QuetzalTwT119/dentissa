@php
    $estadoLabel = collect(\App\Enums\EstadoCita::cases())->mapWithKeys(fn ($estado) => [$estado->value => $estado->etiqueta()])->all();
    $esPersonal = true;
    $porDia = isset($dia);
    $terminales = ['completada', 'cancelada', 'no_asistio'];

    // "Hoy · jueves 19 de noviembre", "Mañana · viernes 20 de noviembre" o "Sábado 21 de noviembre".
    $tituloDia = function (\Illuminate\Support\Carbon $fecha) {
        $larga = $fecha->locale('es')->translatedFormat('l j \d\e F');
        if ($fecha->isToday()) return 'Hoy · '.$larga;
        if ($fecha->isTomorrow()) return 'Mañana · '.$larga;
        if ($fecha->isYesterday()) return 'Ayer · '.$larga;
        return ucfirst($larga).($fecha->year !== now()->year ? ' de '.$fecha->year : '');
    };
    $conEstado = fn (array $parametros) => array_filter($parametros + ['estado' => $filtros['estado'] ?? null]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Control de Agenda"
            title="Agenda"
            subtitle="Las citas de cada día, en orden de hora, con su siguiente paso a un clic."
        >
            <x-slot name="actions">
                @include('citas.partials.selector-vista', ['vistaActual' => 'lista'])
            </x-slot>
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if ($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            {{-- Búsqueda (en todas las fechas) y estado: filtro global (components/filtro). El buscador
                 actualiza solo la zona de resultados de citas, sin recargar. --}}
            <x-admin.card class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-3">
                <x-filtro.barra :action="route('citas.index')" :campos="['buscar', 'estado', 'fecha']" :conservar="['fecha']" resultados="[data-citas-results]">
                    <x-filtro.buscar id="buscar_citas" etiqueta="Buscar citas por paciente, teléfono, servicio o tratamiento" placeholder="Paciente, teléfono, servicio o tratamiento" />
                    <x-filtro.select name="estado" etiqueta="Filtrar por estado" todos="Todos los estados" :opciones="$estadoLabel" />
                </x-filtro.barra>
                <a href="{{ route('citas.create') }}" class="btn-brand min-h-11 shrink-0 px-5">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>
                    <span>Agendar cita</span>
                </a>
            </x-admin.card>

            <div data-citas-results aria-live="polite" aria-busy="false" class="space-y-4">
                @if ($porDia)
                    @php
                        $pendientes = $citasDelDia->reject(fn ($cita) => in_array($cita->estado->value, $terminales, true));
                        $hechas = $citasDelDia->filter(fn ($cita) => in_array($cita->estado->value, $terminales, true));
                        $enCurso = $citasDelDia->first(fn ($cita) => $cita->estado->value === 'en_curso');
                        // QA 24/11/2026: las completadas ya no cuentan como "activas".
                        $activas = $citasDelDia->filter(fn ($cita) => $cita->estado->esPorAtender())->count();
                        $atendidasDia = $citasDelDia->where('estado', \App\Enums\EstadoCita::Completada)->count();
                        $porConfirmar = $citasDelDia->where('estado', \App\Enums\EstadoCita::Agendada)->count();
                        $canceladasDia = $citasDelDia->where('estado', \App\Enums\EstadoCita::Cancelada)->count();
                        $noAsistioDia = $citasDelDia->where('estado', \App\Enums\EstadoCita::NoAsistio)->count();
                        $diaSeleccionado = $pestanas->firstWhere('seleccionada', true);
                        $partesHechas = collect(['completada', 'no_asistio', 'cancelada'])
                            ->map(fn ($estado) => [$estado, $hechas->filter(fn ($cita) => $cita->estado->value === $estado)->count()])
                            ->filter(fn ($par) => $par[1] > 0)
                            ->map(fn ($par) => $par[1].' '.match ($par[0]) {
                                'completada' => $par[1] === 1 ? 'atendida' : 'atendidas',
                                'cancelada' => $par[1] === 1 ? 'cancelada' : 'canceladas',
                                'no_asistio' => $par[1] === 1 ? 'no asistió' : 'no asistieron',
                            })
                            ->join(' · ');
                    @endphp

                    {{-- QA 24/11/2026: citas «En curso» de días anteriores que nadie completó. --}}
                    @if (($enCursoOlvidadas ?? collect())->isNotEmpty())
                        <div role="status" data-en-curso-olvidadas class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            <p class="font-semibold">
                                {{ $enCursoOlvidadas->count() === 1 ? 'Una cita quedó' : $enCursoOlvidadas->count().' citas quedaron' }} «En curso» de días anteriores.
                            </p>
                            <p class="mt-1">
                                {{-- 25/11/2026: dentissa:cerrar-citas-en-curso las completa a las 00:05. --}}
                                Se completarán solas esta noche, o ábrelas y presiona <strong>Completar</strong> ahora:
                                @foreach ($enCursoOlvidadas->groupBy(fn ($cita) => $cita->fecha_hora->toDateString()) as $fecha => $grupo)
                                    <a href="{{ route('citas.index', ['fecha' => $fecha]) }}" class="font-semibold text-amber-900 underline underline-offset-2 hover:text-amber-700">{{ \Illuminate\Support\Carbon::parse($fecha)->locale('es')->translatedFormat('D j M') }} ({{ $grupo->count() }})</a>@if (! $loop->last), @endif
                                @endforeach
                            </p>
                        </div>
                    @endif

                    <!-- Pestañas: un día a la vez -->
                    <div class="flex items-center gap-2">
                        <a href="{{ route('citas.index', $conEstado(['fecha' => $semanaAnterior])) }}" aria-label="Semana anterior"
                           class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-full border border-line bg-white text-ink transition-[border-color,transform] duration-150 ease-out hover:border-peach-300 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 sm:inline-flex">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </a>

                        <nav aria-label="Días de la semana" data-pestanas-dias
                             x-data x-init="$el.querySelector('[aria-current=date]')?.scrollIntoView({ inline: 'center', block: 'nearest' })"
                             class="-mx-4 flex min-w-0 flex-1 snap-x gap-2 overflow-x-auto overscroll-x-contain px-4 py-0.5 [scrollbar-width:none] sm:mx-0 sm:px-0.5 [&::-webkit-scrollbar]:hidden">
                            @foreach ($pestanas as $pestana)
                                <a href="{{ route('citas.index', $conEstado(['fecha' => $pestana['fecha']->toDateString()])) }}"
                                   @if ($pestana['seleccionada']) aria-current="date" @endif
                                   @class([
                                       'flex min-w-[7.5rem] shrink-0 snap-start flex-col gap-0.5 rounded-2xl border px-3.5 py-2.5 transition-[border-color,background-color,transform] duration-150 ease-out active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 sm:min-w-0 sm:flex-1',
                                       'border-brand-600 bg-brand-50 ring-1 ring-inset ring-brand-600' => $pestana['seleccionada'],
                                       'border-line bg-white hover:border-peach-300' => ! $pestana['seleccionada'],
                                   ])>
                                    <span @class(['whitespace-nowrap text-sm font-semibold', 'text-brand-700' => $pestana['seleccionada'], 'text-ink' => ! $pestana['seleccionada']])>
                                        {{ $pestana['esHoy'] ? 'Hoy' : ucfirst(rtrim($pestana['fecha']->locale('es')->isoFormat('ddd'), '.')) }} {{ $pestana['fecha']->day }}
                                    </span>
                                    <span class="whitespace-nowrap text-xs text-muted">
                                        @if ($pestana['total'] === 0 && $pestana['canceladas'] + $pestana['noAsistio'] > 0)
                                            {{ $pestana['canceladas'] + $pestana['noAsistio'] }} {{ $pestana['canceladas'] + $pestana['noAsistio'] === 1 ? 'cancelada' : 'canceladas' }}
                                        @elseif (! $pestana['abierto'] && $pestana['total'] === 0)
                                            Cerrado
                                        @else
                                            {{ $pestana['total'] }} {{ $pestana['total'] === 1 ? 'cita' : 'citas' }}
                                        @endif
                                        @if ($pestana['porConfirmar'] > 0)
                                            · <span class="font-semibold text-amber-800">{{ $pestana['porConfirmar'] }} por confirmar</span>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </nav>

                        <a href="{{ route('citas.index', $conEstado(['fecha' => $semanaSiguiente])) }}" aria-label="Semana siguiente"
                           class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-full border border-line bg-white text-ink transition-[border-color,transform] duration-150 ease-out hover:border-peach-300 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 sm:inline-flex">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2 px-1">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <h2 class="font-serif text-title-3 text-ink">{{ $tituloDia($dia) }}</h2>
                            <p class="text-footnote text-muted">
                                {{ $activas }} por atender
                                @if ($atendidasDia > 0)
                                    · {{ $atendidasDia }} {{ $atendidasDia === 1 ? 'atendida' : 'atendidas' }}
                                @endif
                                @if ($canceladasDia > 0)
                                    · {{ $canceladasDia }} {{ $canceladasDia === 1 ? 'cancelada' : 'canceladas' }}
                                @endif
                                @if ($noAsistioDia > 0)
                                    · {{ $noAsistioDia }} no {{ $noAsistioDia === 1 ? 'asistió' : 'asistieron' }}
                                @endif
                                @if ($porConfirmar > 0)
                                    · <span class="font-semibold text-amber-800">{{ $porConfirmar }} por confirmar</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm font-semibold">
                            {{-- En celular las flechas de semana se esconden; estos enlaces cubren lo mismo. --}}
                            <a href="{{ route('citas.index', $conEstado(['fecha' => $semanaAnterior])) }}" class="inline-flex min-h-11 items-center text-brand-700 sm:hidden">‹ Semana anterior</a>
                            <a href="{{ route('citas.index', $conEstado(['fecha' => $semanaSiguiente])) }}" class="inline-flex min-h-11 items-center text-brand-700 sm:hidden">Semana siguiente ›</a>
                            @unless ($dia->isToday())
                                <a href="{{ route('citas.index', $conEstado([])) }}" class="inline-flex min-h-11 items-center text-brand-700 hover:text-brand-800">Ir a hoy</a>
                            @endunless
                            <a href="{{ route('citas.index', $conEstado(['todas' => 1])) }}" class="inline-flex min-h-11 items-center text-muted hover:text-brand-700">Todas las fechas</a>
                        </div>
                    </div>

                    <div role="table" aria-label="Citas de {{ $tituloDia($dia) }}" class="lista-panel rounded-3xl border border-line bg-white shadow-soft">
                        @if ($citasDelDia->isNotEmpty())
                            <div role="row" class="fila-cita-cabecera rounded-t-3xl border-b border-line bg-shell/50 text-caption font-semibold uppercase tracking-label text-ink-soft">
                                <span role="columnheader">Hora</span>
                                <span role="columnheader">Paciente</span>
                                <span role="columnheader">Tratamiento</span>
                                <span role="columnheader">Estado</span>
                                <span role="columnheader" class="text-right">Acciones</span>
                            </div>
                        @endif

                        {{-- Lo ya atendido o cancelado se pliega en un renglón mientras queden citas por atender. --}}
                        @if ($hechas->isNotEmpty() && $pendientes->isNotEmpty())
                            <details class="group border-b border-line/70" data-citas-plegadas>
                                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 bg-shell/30 px-4 text-sm text-ink-soft transition-colors hover:bg-shell/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-400 lg:px-5 [&::-webkit-details-marker]:hidden">
                                    {{-- QA 24/11/2026: antes "4 ya atendidas o canceladas (4 canceladas)". --}}
                                    <span>{{ $partesHechas }}</span>
                                    <span class="shrink-0 font-semibold text-brand-700"><span class="group-open:hidden">Mostrar</span><span class="hidden group-open:inline">Ocultar</span></span>
                                </summary>
                                @foreach ($hechas as $cita)
                                    @include('citas.partials.fila', ['cita' => $cita])
                                @endforeach
                            </details>
                            @foreach ($pendientes as $cita)
                                @include('citas.partials.fila', ['cita' => $cita, 'resaltada' => $enCurso?->is($cita) && $dia->isToday()])
                            @endforeach
                        @else
                            @foreach ($citasDelDia as $cita)
                                @include('citas.partials.fila', ['cita' => $cita, 'resaltada' => $enCurso?->is($cita) && $dia->isToday()])
                            @endforeach
                        @endif

                        @if ($citasDelDia->isEmpty())
                            <div class="px-6 py-12 text-center text-sm text-muted">
                                <p class="font-serif text-title-3 text-ink">
                                    @if (filled($filtros['estado'] ?? null))
                                        No hay citas «{{ mb_strtolower($estadoLabel[$filtros['estado']]) }}» este día.
                                    @elseif ($diaSeleccionado && ! $diaSeleccionado['abierto'])
                                        El consultorio no abre este día.
                                    @else
                                        Sin citas este día.
                                    @endif
                                </p>
                                <p class="mt-1">Elige otro día en las pestañas o agenda una nueva cita.</p>
                            </div>
                        @endif
                    </div>
                @else
                    @php
                        $grupos = $citas->getCollection()->groupBy(fn ($cita) => $cita->fecha_hora->toDateString());
                    @endphp

                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2 px-1">
                        <h2 class="font-serif text-title-3 text-ink">
                            {{ filled($filtros['buscar'] ?? null) ? 'Resultados en todas las fechas' : 'Todas las fechas' }}
                        </h2>
                        <a href="{{ route('citas.index', $conEstado([])) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-brand-700 hover:text-brand-800">‹ Volver a la agenda por día</a>
                    </div>

                    <x-admin.card>
                        <x-pagination-summary :items="$citas" label="citas" class="rounded-t-3xl" />
                        @forelse ($grupos as $fecha => $citasDeFecha)
                            <section aria-label="{{ $tituloDia(\Illuminate\Support\Carbon::parse($fecha)) }}" @class(['border-t border-line' => ! $loop->first])>
                                <h3 @class(['flex items-center justify-between gap-3 bg-shell/50 px-4 py-2.5 text-sm font-semibold text-ink lg:px-5', 'rounded-t-3xl' => $loop->first])>
                                    <span>{{ $tituloDia(\Illuminate\Support\Carbon::parse($fecha)) }}</span>
                                    <a href="{{ route('citas.index', $conEstado(['fecha' => $fecha])) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">Ver el día</a>
                                </h3>
                                <div role="table" aria-label="Citas del {{ $fecha }}">
                                    @foreach ($citasDeFecha as $cita)
                                        @include('citas.partials.fila', ['cita' => $cita])
                                    @endforeach
                                </div>
                            </section>
                        @empty
                            <div class="px-6 py-12 text-center text-sm text-muted">
                                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-peach-50 text-brand-600 ring-1 ring-inset ring-peach-200">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="font-serif text-title-3 text-ink">No hay citas registradas para este criterio.</p>
                                <p class="mt-1 text-sm">Prueba con otro nombre o teléfono, o agenda una nueva cita.</p>
                            </div>
                        @endforelse

                        <x-pagination-summary :items="$citas" label="citas" class="rounded-b-3xl" posicion="abajo" />
                    </x-admin.card>
                @endif
            </div>
        </div>
    </div>

</x-app-layout>

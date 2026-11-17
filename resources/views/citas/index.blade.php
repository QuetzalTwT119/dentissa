@php
$estadoLabel = collect(\App\Enums\EstadoCita::cases())->mapWithKeys(fn ($estado) => [$estado->value => $estado->etiqueta()])->all();
$estadoClase = collect(\App\Enums\EstadoCita::cases())->mapWithKeys(fn ($estado) => [$estado->value => $estado->claseInsignia()])->all();
$estadoDot = [
    'agendada' => 'bg-amber-500',
    'confirmada' => 'bg-sky-500',
    'en_curso' => 'bg-brand-500 animate-pulse',
    'completada' => 'bg-sage-500',
    'cancelada' => 'bg-muted',
    'no_asistio' => 'bg-rose-500',
];
$esPersonal = true;
$esAdmin = auth()->user()->rol === \App\Enums\RolUsuario::Admin;
$estadosConAccionesSecundarias = ['agendada', 'confirmada'];
$estadosTerminales = ['completada', 'cancelada', 'no_asistio'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Control de Agenda"
            :title="$esPersonal ? 'Citas' : 'Mis citas'"
            subtitle="Monitoreo de citas médicas, estados de atención y tiempos clínicos."
        />
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            @if (session('error'))
                <x-alert type="error" :message="session('error')" />
            @endif

            @if ($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            @include('citas.partials.selector-vista', ['vistaActual' => 'lista'])

            <!-- Filtros y Barra de Acciones Superior -->
            <div class="rounded-3xl border border-line bg-white p-4 shadow-soft sm:p-6">
                @if ($esPersonal)
                    <form method="GET" action="{{ route('citas.index') }}" role="search" data-citas-live-search class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-12 xl:items-end">
                        @if (request()->has('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif

                        <div class="min-w-0 xl:col-span-4">
                            <label for="buscar_citas" class="mb-1.5 block text-sm font-semibold text-ink">Buscar</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-brand-500" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4" stroke-linecap="round"/></svg>
                                </span>
                                <input enterkeyhint="search" id="buscar_citas" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="100"
                                       placeholder="Paciente, teléfono, servicio o tratamiento"
                                       aria-label="Buscar citas por paciente, teléfono, servicio o tratamiento"
                                       x-on:input.debounce.600ms="$el.form.requestSubmit()"
                                       class="block h-12 w-full rounded-xl border border-field bg-cream/60 pl-11 pr-4 text-sm text-ink shadow-2xs transition-[border-color,box-shadow,background-color] placeholder:text-muted hover:border-peach-300 hover:bg-white focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                            </div>
                        </div>

                        <div class="min-w-0 xl:col-span-2">
                            <label for="filtro_fecha" class="mb-1.5 block text-sm font-semibold text-ink">Fecha</label>
                            <input id="filtro_fecha" type="date" name="fecha" value="{{ $filtros['fecha'] ?? '' }}" onchange="this.form.submit()"
                                   class="h-12 w-full rounded-xl border border-field bg-cream/60 px-3 text-sm text-ink shadow-2xs transition-[border-color,box-shadow,background-color] hover:border-peach-300 hover:bg-white focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                        </div>

                        <div class="min-w-0 xl:col-span-2">
                            <label for="filtro_estado" class="mb-1.5 block text-sm font-semibold text-ink">Estado</label>
                            <select id="filtro_estado" name="estado" onchange="this.form.submit()"
                                    class="h-12 w-full rounded-xl border border-field bg-cream/60 px-3 text-sm text-ink shadow-2xs transition-[border-color,box-shadow,background-color] hover:border-peach-300 hover:bg-white focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-brand-500/15">
                                <option value="">Todos los estados</option>
                                @foreach ($estadoLabel as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="sr-only">Buscar citas</button>

                        <div class="flex items-center gap-2 sm:col-span-2 xl:col-span-4 xl:justify-end">
                            <a data-citas-clear
                               href="{{ route('citas.index', request()->only('per_page')) }}"
                               @class([
                                   'inline-flex h-12 items-center justify-center rounded-full px-4 text-sm font-semibold text-muted transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-400',
                                   'hidden' => empty($filtros['buscar']) && empty($filtros['fecha']) && empty($filtros['estado']),
                               ])>
                                Limpiar
                            </a>
                            <a href="{{ route('citas.create') }}" class="btn-brand h-12 shrink-0 px-5">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                <span>Agendar cita</span>
                            </a>
                        </div>
                    </form>
                @else
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-sm text-ink-soft">Aquí puedes consultar el estado y horario de tus citas programadas.</p>
                        <a href="{{ route('citas.create') }}" class="btn-brand shrink-0">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Agendar cita</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Tabla de Citas -->
            <div data-citas-results aria-live="polite" aria-busy="false" class="overflow-hidden rounded-3xl border border-line bg-white shadow-soft">
                <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="border-b border-line bg-shell/50 text-caption font-semibold uppercase tracking-label text-ink-soft">
                        <tr>
                            <th class="px-6 py-3.5">Fecha y hora</th>
                            @if ($esPersonal)
                                <th class="px-6 py-3.5">Paciente</th>
                            @endif
                            <th class="px-6 py-3.5">Servicio</th>
                            <th class="px-6 py-3.5">Tratamiento</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/70">
                        @forelse ($citas as $cita)
                            <tr class="transition-colors hover:bg-cream/70">
                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-ink">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-peach-50 text-brand-600 ring-1 ring-inset ring-peach-200">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-ink">{{ \Illuminate\Support\Carbon::parse($cita->fecha_hora)->translatedFormat('d M Y') }}</p>
                                            <p class="text-xs font-medium text-muted">{{ \Illuminate\Support\Carbon::parse($cita->fecha_hora)->format('H:i') }} hrs</p>
                                        </div>
                                    </div>
                                </td>
                                @if ($esPersonal)
                                    <td class="px-6 py-4 font-medium text-ink">
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-peach-100 to-brand-100 text-xs font-semibold uppercase text-brand-800">
                                                {{ substr($cita->paciente->nombre, 0, 1) }}{{ substr($cita->paciente->apellido, 0, 1) }}
                                            </span>
                                            <div>
                                                <p class="text-sm font-semibold text-ink">{{ $cita->paciente->nombre }} {{ $cita->paciente->apellido }}</p>
                                                <p class="text-xs text-muted">{{ $cita->paciente->telefono ?: $cita->paciente->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-cream px-2.5 py-1 text-xs font-medium text-ink ring-1 ring-inset ring-line">
                                        {{ $cita->servicio->nombre ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-ink-soft font-medium">{{ $cita->tratamiento->nombre ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoClase[$cita->estado->value] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $estadoDot[$cita->estado->value] ?? 'bg-muted' }}"></span>
                                        {{ $estadoLabel[$cita->estado->value] }}
                                    </span>
                                    @if ($cita->asistencia_confirmada_en && ! $cita->estado->esInactiva())
                                        <span class="mt-1 block text-xs font-medium text-sage-700" data-asistencia-confirmada>✓ Paciente confirmó asistencia</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex w-max max-w-full items-center justify-center gap-2">
                                        @if ($esPersonal && $cita->estado->value === 'agendada')
                                            <form method="POST" action="{{ route('citas.confirm', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center whitespace-nowrap rounded-full bg-brand-600 px-4 py-2.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
                                                    Confirmar
                                                </button>
                                            </form>
                                        @elseif ($esPersonal && $cita->estado->value === 'confirmada')
                                            <form method="POST" action="{{ route('citas.start', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex h-[42px] items-center justify-center gap-1.5 whitespace-nowrap rounded-full bg-brand-600 px-3.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.5 4.75a.75.75 0 011.143-.64l8 5a.75.75 0 010 1.28l-8 5A.75.75 0 016.5 14.75v-10z" /></svg>
                                                    Iniciar
                                                </button>
                                            </form>
                                        @elseif ($esPersonal && $cita->estado->value === 'en_curso')
                                            <form method="POST" action="{{ route('citas.complete', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center whitespace-nowrap rounded-full bg-sage-600 px-4 py-2.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-sage-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sage-500 focus-visible:ring-offset-2">
                                                    Completar
                                                </button>
                                            </form>
                                        @endif

                                        @if ($esPersonal && in_array($cita->estado->value, $estadosConAccionesSecundarias, true))
                                            <details class="group relative">
                                                <summary aria-label="Abrir más acciones de la cita" title="Más acciones" class="inline-flex h-[42px] cursor-pointer list-none items-center justify-center gap-2 rounded-full border border-line bg-white px-3 text-xs font-semibold text-ink-soft shadow-2xs transition-colors duration-150 hover:border-peach-300 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 [&::-webkit-details-marker]:hidden">
                                                    <span class="flex h-6 w-6 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path d="M10 4.25a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zm0 4.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zm0 4.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5z" />
                                                        </svg>
                                                    </span>
                                                    Más acciones
                                                    <svg class="h-3.5 w-3.5 text-muted transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
                                                </summary>
                                                <div x-data="{}" x-init="$nextTick(() => { const detail = $el.closest('details'); detail?.addEventListener('toggle', () => { if (!detail.open) return; const trigger = detail.querySelector('summary').getBoundingClientRect(); const scrollArea = detail.closest('.overflow-x-auto')?.getBoundingClientRect(); const top = scrollArea?.top ?? 0; const bottom = scrollArea?.bottom ?? window.innerHeight; const roomBelow = bottom - trigger.bottom; const roomAbove = trigger.top - top; $el.classList.toggle('bottom-full', roomBelow < 220 && roomAbove > roomBelow); $el.classList.toggle('top-full', !(roomBelow < 220 && roomAbove > roomBelow)); $el.classList.toggle('mb-2', roomBelow < 220 && roomAbove > roomBelow); $el.classList.toggle('mt-2', !(roomBelow < 220 && roomAbove > roomBelow)); }); })" class="absolute right-0 top-full z-30 mt-2 flex min-w-48 flex-col rounded-2xl border border-line bg-white p-1.5 shadow-lift">
                                                    <p class="px-3 pb-2 pt-1 text-caption font-semibold uppercase tracking-label text-muted">Gestionar cita</p>
                                                    {{-- Misma regla que CitaController@noShow: solo después de la hora de la cita. --}}
                                                    @if ($cita->estado->value === 'confirmada' && ! $cita->fecha_hora->isFuture())
                                                        <form method="POST" action="{{ route('citas.no-show', $cita) }}"
                                                              onsubmit="return confirm('¿Marcar esta cita como no asistida? Esta acción no se puede deshacer.');">
                                                            @csrf
                                                            @method('patch')
                                                            <button type="submit" class="flex w-full items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-left text-sm font-medium text-ink-soft transition hover:bg-amber-50 hover:text-amber-800 focus:bg-amber-50 focus:outline-none">
                                                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v3.5c0 .414.336.75.75.75h2a.75.75 0 000-1.5h-1.25v-2.75z" clip-rule="evenodd" /></svg>
                                                                </span>
                                                                Marcar inasistencia
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('citas.edit', $cita) }}" class="flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium text-ink-soft transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800 focus:outline-none">
                                                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 20 20" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M10 4v2m0 8v2m6-6h-2M6 10H4m10.243-4.243-1.414 1.414M7.172 12.828l-1.414 1.414m8.485 0-1.414-1.414M7.172 7.172 5.758 5.758" /><circle cx="10" cy="10" r="3" stroke-width="1.6" /></svg>
                                                        </span>
                                                        Reagendar
                                                    </a>
                                                    <div class="mx-2 my-1 border-t border-line"></div>
                                                    <form method="POST" action="{{ route('citas.cancel', $cita) }}"
                                                          onsubmit="const motivo = prompt('Motivo de la cancelación (opcional):') ?? ''; if (!confirm('¿Cancelar esta cita?')) { return false; } this.querySelector('[name=motivo_cancelacion]').value = motivo; return true;">
                                                        @csrf
                                                        @method('patch')
                                                        <input type="hidden" name="motivo_cancelacion" value="">
                                                        <button type="submit" class="flex w-full items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rose-700 transition hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                                                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 20 20" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m6 6 8 8M14 6l-8 8" /></svg>
                                                            </span>
                                                            Cancelar cita
                                                        </button>
                                                    </form>
                                                </div>
                                            </details>
                                        @elseif ($esPersonal && in_array($cita->estado->value, $estadosTerminales, true))
                                            <span class="text-xs text-muted">Sin acciones</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $esPersonal ? 6 : 5 }}" class="px-6 py-12 text-center text-sm text-muted">
                                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-peach-50 text-brand-600 ring-1 ring-inset ring-peach-200">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="font-serif text-title-3 text-ink">No hay citas registradas para este criterio.</p>
                                    <p class="mt-1 text-sm">Puedes utilizar los filtros superiores o agendar una nueva cita.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <x-pagination-summary :items="$citas" label="citas" />
            </div>
        </div>
    </div>

    @if ($esPersonal)
        <script>
            (() => {
                const inicializarBusquedaEnVivo = () => {
                    const form = document.querySelector('[data-citas-live-search]');
                    const resultados = document.querySelector('[data-citas-results]');
                    const entrada = form?.querySelector('[name="buscar"]');
                    const limpiar = form?.querySelector('[data-citas-clear]');

                    if (!form || !resultados || !entrada || form.dataset.liveSearchReady) return;

                    form.dataset.liveSearchReady = 'true';
                    let temporizador = null;
                    let solicitud = null;

                    entrada.addEventListener('input', () => {
                        clearTimeout(temporizador);
                        solicitud?.abort();

                        temporizador = setTimeout(async () => {
                            const parametros = new URLSearchParams(new FormData(form));
                            const url = `${form.action}?${parametros.toString()}`;
                            solicitud = new AbortController();
                            resultados.setAttribute('aria-busy', 'true');

                            try {
                                const respuesta = await fetch(url, {
                                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                    signal: solicitud.signal,
                                });

                                if (!respuesta.ok) throw new Error('No se pudieron actualizar las citas.');

                                const html = await respuesta.text();
                                const documento = new DOMParser().parseFromString(html, 'text/html');
                                const resultadosNuevos = documento.querySelector('[data-citas-results]');

                                if (!resultadosNuevos) throw new Error('No se encontró la lista actualizada de citas.');

                                resultados.innerHTML = resultadosNuevos.innerHTML;
                                resultados.setAttribute('aria-busy', 'false');
                                window.history.replaceState({}, '', url);

                                const hayFiltros = ['buscar', 'fecha', 'estado'].some((nombre) =>
                                    String(form.elements.namedItem(nombre)?.value ?? '').trim() !== ''
                                );
                                limpiar?.classList.toggle('hidden', !hayFiltros);
                                window.Alpine?.initTree(resultados);
                            } catch (error) {
                                if (error.name !== 'AbortError') {
                                    resultados.setAttribute('aria-busy', 'false');
                                    console.error(error);
                                }
                            }
                        }, 180);
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', inicializarBusquedaEnVivo, { once: true });
                } else {
                    inicializarBusquedaEnVivo();
                }
            })();
        </script>
    @endif
</x-app-layout>

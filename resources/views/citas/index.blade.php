@php
$estadoLabel = [
    'agendada' => 'Agendada',
    'confirmada' => 'Confirmada',
    'en_curso' => 'En curso',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
    'no_asistio' => 'No asistió',
];
$estadoClase = [
    'agendada' => 'bg-amber-50 text-amber-800 border border-amber-200/70',
    'confirmada' => 'bg-sky-50 text-sky-800 border border-sky-200/70',
    'en_curso' => 'bg-brand-50 text-brand-800 border border-brand-200/70',
    'completada' => 'bg-[#EAF4EE] text-[#4E8D72] border border-[#4E8D72]/25',
    'cancelada' => 'bg-gray-100 text-gray-500 border border-gray-200/70',
    'no_asistio' => 'bg-rose-50 text-rose-800 border border-rose-200/70',
];
$estadoDot = [
    'agendada' => 'bg-amber-500',
    'confirmada' => 'bg-sky-500',
    'en_curso' => 'bg-brand-500 animate-pulse',
    'completada' => 'bg-[#4E8D72]',
    'cancelada' => 'bg-gray-400',
    'no_asistio' => 'bg-rose-500',
];
$esPersonal = true;
$esAdmin = auth()->user()->rol === \App\Enums\RolUsuario::Admin;
$estadosConAccionesSecundarias = ['agendada', 'confirmada'];
$estadosTerminales = ['completada', 'cancelada', 'no_asistio'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-600 bg-brand-50 px-2.5 py-0.5 rounded-full border border-brand-200/50">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span> Control de Agenda
                    </span>
                </div>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-[#2D2526] sm:text-3xl">
                    {{ $esPersonal ? 'Citas' : 'Mis citas' }}
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm text-[#8A7A7D]">Monitoreo de citas médicas, estados de atención y tiempos clínicos.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm font-semibold text-emerald-800 shadow-soft flex items-center gap-3">
                    <svg class="h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm font-semibold text-rose-700 shadow-soft">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm font-semibold text-rose-700 shadow-soft">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Filtros y Barra de Acciones Superior -->
            <div class="rounded-3xl border border-[#e9dfdb] bg-white p-5 sm:p-6 shadow-soft">
                @if ($esPersonal)
                    <form method="GET" action="{{ route('citas.index') }}" role="search" data-citas-live-search class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-12 xl:items-end">
                        @if (request()->has('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif

                        <div class="min-w-0 xl:col-span-4">
                            <label for="buscar_citas" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#76676b]">Buscar</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#d75078]" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4" stroke-linecap="round"/></svg>
                                </span>
                                <input id="buscar_citas" type="search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" maxlength="100"
                                       placeholder="Paciente, teléfono, servicio o tratamiento"
                                       aria-label="Buscar citas por paciente, teléfono, servicio o tratamiento"
                                       x-on:input.debounce.600ms="$el.form.requestSubmit()"
                                       class="block h-12 w-full rounded-xl border border-[#e9dfdb] bg-[#FAF6F6]/60 pl-11 pr-4 text-sm text-[#2D2526] shadow-sm transition placeholder:text-[#8A7A7D]/70 hover:border-brand-300 hover:bg-white focus:border-[#d75078] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#d75078]/20">
                            </div>
                        </div>

                        <div class="min-w-0 xl:col-span-2">
                            <label for="filtro_fecha" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#76676b]">Fecha</label>
                            <input id="filtro_fecha" type="date" name="fecha" value="{{ $filtros['fecha'] ?? '' }}" onchange="this.form.submit()"
                                   class="h-12 w-full rounded-xl border border-[#e9dfdb] bg-[#FAF6F6]/60 px-3 text-sm text-[#2D2526] shadow-sm transition hover:border-brand-300 hover:bg-white focus:border-[#d75078] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#d75078]/20">
                        </div>

                        <div class="min-w-0 xl:col-span-2">
                            <label for="filtro_estado" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-[#76676b]">Estado</label>
                            <select id="filtro_estado" name="estado" onchange="this.form.submit()"
                                    class="h-12 w-full rounded-xl border border-[#e9dfdb] bg-[#FAF6F6]/60 px-3 text-sm text-[#2D2526] shadow-sm transition hover:border-brand-300 hover:bg-white focus:border-[#d75078] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#d75078]/20">
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
                                   'inline-flex h-12 items-center justify-center rounded-xl px-3 text-xs font-semibold text-[#8A7A7D] transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-400',
                                   'hidden' => empty($filtros['buscar']) && empty($filtros['fecha']) && empty($filtros['estado']),
                               ])>
                                Limpiar
                            </a>
                            <a href="{{ route('citas.create') }}" class="btn-brand inline-flex h-12 shrink-0 items-center justify-center rounded-xl px-4 text-xs">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                <span>Agendar cita</span>
                            </a>
                        </div>
                    </form>
                @else
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-sm text-[#6b5e62]">Aquí puedes consultar el estado y horario de tus citas programadas.</p>
                        <a href="{{ route('citas.create') }}" class="btn-brand shrink-0">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Agendar cita</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Tabla de Citas -->
            <div data-citas-results aria-live="polite" aria-busy="false" class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
                <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
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
                    <tbody class="divide-y divide-[#e9dfdb]/60">
                        @forelse ($citas as $cita)
                            <tr class="transition hover:bg-[#FAF6F6]/60">
                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-[#2D2526]">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 border border-brand-100">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-[#2D2526]">{{ \Illuminate\Support\Carbon::parse($cita->fecha_hora)->translatedFormat('d M Y') }}</p>
                                            <p class="text-xs font-medium text-[#8A7A7D]">{{ \Illuminate\Support\Carbon::parse($cita->fecha_hora)->format('H:i') }} hrs</p>
                                        </div>
                                    </div>
                                </td>
                                @if ($esPersonal)
                                    <td class="px-6 py-4 font-medium text-[#2D2526]">
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-[#f2b0a6] to-[#d75078] text-white text-xs font-bold uppercase shadow-xs">
                                                {{ substr($cita->paciente->nombre, 0, 1) }}{{ substr($cita->paciente->apellido, 0, 1) }}
                                            </span>
                                            <div>
                                                <p class="text-sm font-semibold text-[#2D2526]">{{ $cita->paciente->nombre }} {{ $cita->paciente->apellido }}</p>
                                                <p class="text-xs text-[#8A7A7D]">{{ $cita->paciente->telefono ?: $cita->paciente->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-lg bg-[#FAF6F6] px-2.5 py-1 text-xs font-medium text-[#2D2526] border border-[#e9dfdb]">
                                        {{ $cita->servicio->nombre ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-[#6b5e62] font-medium">{{ $cita->tratamiento->nombre ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $estadoClase[$cita->estado->value] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $estadoDot[$cita->estado->value] ?? 'bg-gray-400' }}"></span>
                                        {{ $estadoLabel[$cita->estado->value] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex w-max max-w-full items-center justify-center gap-2">
                                        @if ($esPersonal && $cita->estado->value === 'agendada')
                                            <form method="POST" action="{{ route('citas.confirm', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center whitespace-nowrap rounded-xl bg-brand-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
                                                    Confirmar
                                                </button>
                                            </form>
                                        @elseif ($esPersonal && $cita->estado->value === 'confirmada')
                                            <form method="POST" action="{{ route('citas.start', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex h-[42px] items-center justify-center gap-1.5 whitespace-nowrap rounded-xl bg-brand-600 px-3.5 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.5 4.75a.75.75 0 011.143-.64l8 5a.75.75 0 010 1.28l-8 5A.75.75 0 016.5 14.75v-10z" /></svg>
                                                    Iniciar
                                                </button>
                                            </form>
                                        @elseif ($esPersonal && $cita->estado->value === 'en_curso')
                                            <form method="POST" action="{{ route('citas.complete', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2">
                                                    Completar
                                                </button>
                                            </form>
                                        @endif

                                        @if ($esPersonal && in_array($cita->estado->value, $estadosConAccionesSecundarias, true))
                                            <details class="group relative">
                                                <summary aria-label="Abrir más acciones de la cita" title="Más acciones" class="inline-flex h-[42px] cursor-pointer list-none items-center justify-center gap-2 rounded-xl border border-[#e9dfdb] bg-white px-3 text-xs font-semibold text-[#51464a] shadow-sm transition duration-200 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2 [&::-webkit-details-marker]:hidden">
                                                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path d="M10 4.25a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zm0 4.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zm0 4.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5z" />
                                                        </svg>
                                                    </span>
                                                    Más acciones
                                                    <svg class="h-3.5 w-3.5 text-[#8A7A7D] transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
                                                </summary>
                                                <div x-data="{}" x-init="$nextTick(() => { const detail = $el.closest('details'); detail?.addEventListener('toggle', () => { if (!detail.open) return; const trigger = detail.querySelector('summary').getBoundingClientRect(); const scrollArea = detail.closest('.overflow-x-auto')?.getBoundingClientRect(); const top = scrollArea?.top ?? 0; const bottom = scrollArea?.bottom ?? window.innerHeight; const roomBelow = bottom - trigger.bottom; const roomAbove = trigger.top - top; $el.classList.toggle('bottom-full', roomBelow < 220 && roomAbove > roomBelow); $el.classList.toggle('top-full', !(roomBelow < 220 && roomAbove > roomBelow)); $el.classList.toggle('mb-2', roomBelow < 220 && roomAbove > roomBelow); $el.classList.toggle('mt-2', !(roomBelow < 220 && roomAbove > roomBelow)); }); })" class="absolute right-0 top-full z-30 mt-2 flex min-w-48 flex-col rounded-2xl border border-[#eee3e0] bg-white p-2 shadow-[0_16px_40px_-18px_rgba(72,35,48,0.38)] ring-1 ring-black/[0.03]">
                                                    <p class="px-3 pb-2 pt-1 text-[10px] font-bold uppercase tracking-[0.14em] text-[#9a858b]">Gestionar cita</p>
                                                    @if ($cita->estado->value === 'confirmada')
                                                        <form method="POST" action="{{ route('citas.no-show', $cita) }}">
                                                            @csrf
                                                            @method('patch')
                                                            <button type="submit" class="flex w-full items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-left text-sm font-medium text-[#675354] transition hover:bg-amber-50 hover:text-amber-800 focus:bg-amber-50 focus:outline-none">
                                                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                                                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v3.5c0 .414.336.75.75.75h2a.75.75 0 000-1.5h-1.25v-2.75z" clip-rule="evenodd" /></svg>
                                                                </span>
                                                                Marcar inasistencia
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('citas.edit', $cita) }}" class="flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium text-[#51464a] transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800 focus:outline-none">
                                                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 20 20" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M10 4v2m0 8v2m6-6h-2M6 10H4m10.243-4.243-1.414 1.414M7.172 12.828l-1.414 1.414m8.485 0-1.414-1.414M7.172 7.172 5.758 5.758" /><circle cx="10" cy="10" r="3" stroke-width="1.6" /></svg>
                                                        </span>
                                                        Reagendar
                                                    </a>
                                                    <div class="mx-2 my-1 border-t border-[#f1e9e6]"></div>
                                                    <form method="POST" action="{{ route('citas.cancel', $cita) }}"
                                                          onsubmit="const motivo = prompt('Motivo de la cancelación (opcional):') ?? ''; if (!confirm('¿Cancelar esta cita?')) { return false; } this.querySelector('[name=motivo_cancelacion]').value = motivo; return true;">
                                                        @csrf
                                                        @method('patch')
                                                        <input type="hidden" name="motivo_cancelacion" value="">
                                                        <button type="submit" class="flex w-full items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rose-700 transition hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                                                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 20 20" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m6 6 8 8M14 6l-8 8" /></svg>
                                                            </span>
                                                            Cancelar cita
                                                        </button>
                                                    </form>
                                                </div>
                                            </details>
                                        @elseif ($esPersonal && in_array($cita->estado->value, $estadosTerminales, true))
                                            <span class="text-xs text-[#8A7A7D]">Sin acciones</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $esPersonal ? 6 : 5 }}" class="px-6 py-12 text-center text-sm text-[#8A7A7D]">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 border border-brand-200/50 mb-3">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="font-medium text-[#2D2526]">No hay citas registradas para este criterio.</p>
                                    <p class="mt-1 text-xs">Puedes utilizar los filtros superiores o agendar una nueva cita.</p>
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

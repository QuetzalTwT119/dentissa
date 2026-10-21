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
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
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
            <div class="flex items-center gap-2.5">
                <a href="{{ route('citas.create') }}" class="btn-brand">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Agendar cita</span>
                </a>
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
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-5 shadow-soft">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    @if ($esPersonal)
                        <form method="GET" action="{{ route('citas.index') }}" class="flex flex-wrap items-end gap-3 sm:gap-4">
                            <div>
                                <x-input-label for="filtro_fecha" value="Fecha" class="text-xs uppercase tracking-wider text-[#8A7A7D]" />
                                <input id="filtro_fecha" type="date" name="fecha" value="{{ $filtros['fecha'] ?? '' }}"
                                       onchange="this.form.submit()"
                                       class="mt-1 block rounded-xl border border-[#e9dfdb] bg-[#FAF6F6]/50 px-3 py-2 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-200">
                            </div>
                            <div>
                                <x-input-label for="filtro_estado" value="Estado" class="text-xs uppercase tracking-wider text-[#8A7A7D]" />
                                <select id="filtro_estado" name="estado" onchange="this.form.submit()"
                                        class="mt-1 block rounded-xl border border-[#e9dfdb] bg-[#FAF6F6]/50 px-3 py-2 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <option value="">Todos los estados</option>
                                    @foreach ($estadoLabel as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if (!empty($filtros['fecha']) || !empty($filtros['estado']))
                                <a href="{{ route('citas.index') }}" class="text-xs font-semibold text-[#8A7A7D] hover:text-brand-600 transition pb-2.5">
                                    Limpiar filtros
                                </a>
                            @endif
                        </form>
                    @else
                        <p class="text-sm text-[#6b5e62]">Aquí puedes consultar el estado y horario de tus citas programadas.</p>
                    @endif

                    <a href="{{ route('citas.create') }}" class="btn-brand">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Agendar cita</span>
                    </a>
                </div>
            </div>

            <!-- Tabla de Citas -->
            <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
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
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($esPersonal && $cita->estado->value === 'agendada')
                                            <form method="POST" action="{{ route('citas.confirm', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex items-center whitespace-nowrap rounded-xl bg-brand-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
                                                    Confirmar
                                                </button>
                                            </form>
                                        @elseif ($esPersonal && $cita->estado->value === 'confirmada')
                                            <form method="POST" action="{{ route('citas.start', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex items-center whitespace-nowrap rounded-xl bg-brand-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
                                                    Iniciar
                                                </button>
                                            </form>
                                        @elseif ($esPersonal && $cita->estado->value === 'en_curso')
                                            <form method="POST" action="{{ route('citas.complete', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex items-center whitespace-nowrap rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2">
                                                    Completar
                                                </button>
                                            </form>
                                        @endif

                                        @if ($esPersonal && in_array($cita->estado->value, $estadosConAccionesSecundarias, true))
                                            <details class="group relative">
                                                <summary class="inline-flex cursor-pointer list-none items-center gap-1 whitespace-nowrap rounded-xl border border-[#e9dfdb] bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wider text-[#51464a] shadow-sm transition hover:bg-[#FAF6F6] hover:text-brand-700 hover:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-400">
                                                    Más acciones
                                                    <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                                    </svg>
                                                </summary>
                                                <div class="absolute right-0 top-11 z-30 flex min-w-36 flex-col gap-1 rounded-xl border border-[#e9dfdb] bg-white p-1.5 shadow-xl">
                                                    @if ($cita->estado->value === 'confirmada')
                                                        <form method="POST" action="{{ route('citas.no-show', $cita) }}">
                                                            @csrf
                                                            @method('patch')
                                                            <button type="submit" class="whitespace-nowrap rounded-lg px-3 py-2 text-left text-xs font-medium text-amber-800 transition hover:bg-amber-50 focus:bg-amber-50 focus:outline-none w-full">No asistió</button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('citas.edit', $cita) }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-medium text-[#403539] transition hover:bg-brand-50 hover:text-brand-700 focus:bg-brand-50 focus:outline-none">Reagendar</a>
                                                    <form method="POST" action="{{ route('citas.cancel', $cita) }}"
                                                          onsubmit="const motivo = prompt('Motivo de la cancelación (opcional):') ?? ''; if (!confirm('¿Cancelar esta cita?')) { return false; } this.querySelector('[name=motivo_cancelacion]').value = motivo; return true;">
                                                        @csrf
                                                        @method('patch')
                                                        <input type="hidden" name="motivo_cancelacion" value="">
                                                        <button type="submit" class="whitespace-nowrap rounded-lg px-3 py-2 text-left text-xs font-medium text-rose-700 transition hover:bg-rose-50 focus:bg-rose-50 focus:outline-none w-full">Cancelar</button>
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
            </div>
        </div>
    </div>
</x-app-layout>

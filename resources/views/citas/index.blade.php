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
    'agendada' => 'bg-yellow-100 text-yellow-800',
    'confirmada' => 'bg-blue-100 text-blue-800',
    'en_curso' => 'bg-brand-100 text-brand-800',
    'completada' => 'bg-green-100 text-green-800',
    'cancelada' => 'bg-gray-100 text-gray-500',
    'no_asistio' => 'bg-red-100 text-red-800',
];
$esPersonal = true;
$esAdmin = auth()->user()->rol === \App\Enums\RolUsuario::Admin;
$estadosConAccionesSecundarias = ['agendada', 'confirmada'];
$estadosTerminales = ['completada', 'cancelada', 'no_asistio'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ $esPersonal ? 'Citas' : 'Mis citas' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div role="status" class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap items-end justify-between gap-4 p-6">
                    @if ($esPersonal)
                        <form method="GET" action="{{ route('citas.index') }}" class="flex flex-wrap items-end gap-4">
                            <div>
                                <x-input-label for="filtro_fecha" value="Fecha" />
                                <input id="filtro_fecha" type="date" name="fecha" value="{{ $filtros['fecha'] ?? '' }}"
                                       onchange="this.form.submit()"
                                       class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            </div>
                            <div>
                                <x-input-label for="filtro_estado" value="Estado" />
                                <select id="filtro_estado" name="estado" onchange="this.form.submit()"
                                        class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    <option value="">Todos</option>
                                    @foreach ($estadoLabel as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    @else
                        <p class="text-sm text-gray-600">Aquí puedes ver y gestionar tus citas.</p>
                    @endif

                    <a href="{{ route('citas.create') }}">
                        <x-primary-button>Agendar cita</x-primary-button>
                    </a>
                </div>

                <div class="overflow-x-auto sm:rounded-b-lg">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Fecha y hora</th>
                            @if ($esPersonal)
                                <th class="px-6 py-3">Paciente</th>
                            @endif
                            <th class="px-6 py-3">Servicio</th>
                            <th class="px-6 py-3">Tratamiento</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($citas as $cita)
                            <tr class="border-b border-gray-100">
                                <td class="whitespace-nowrap px-6 py-4 text-gray-900">
                                    {{ \Illuminate\Support\Carbon::parse($cita->fecha_hora)->translatedFormat('d M Y, H:i') }}
                                </td>
                                @if ($esPersonal)
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ $cita->paciente->nombre }} {{ $cita->paciente->apellido }}
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-gray-600">{{ $cita->servicio->nombre ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $cita->tratamiento->nombre ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium {{ $estadoClase[$cita->estado->value] }}">
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
                                                        class="inline-flex items-center whitespace-nowrap rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                                    Confirmar
                                                </button>
                                            </form>
                                        @elseif ($esPersonal && $cita->estado->value === 'confirmada')
                                            <form method="POST" action="{{ route('citas.start', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex items-center whitespace-nowrap rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                                    Iniciar
                                                </button>
                                            </form>
                                        @elseif ($esPersonal && $cita->estado->value === 'en_curso')
                                            <form method="POST" action="{{ route('citas.complete', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit"
                                                        class="inline-flex items-center whitespace-nowrap rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                                    Completar
                                                </button>
                                            </form>
                                        @endif

                                        @if ($esPersonal && in_array($cita->estado->value, $estadosConAccionesSecundarias, true))
                                            <details class="group">
                                                <summary class="inline-flex cursor-pointer list-none items-center gap-1 whitespace-nowrap rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                                    Más acciones
                                                    <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                                    </svg>
                                                </summary>
                                                <div class="mt-2 flex flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-2 shadow-sm">
                                                    @if ($cita->estado->value === 'confirmada')
                                                        <form method="POST" action="{{ route('citas.no-show', $cita) }}">
                                                            @csrf
                                                            @method('patch')
                                                            <button type="submit" class="whitespace-nowrap rounded-md px-3 py-2 text-sm text-amber-800 transition hover:bg-amber-50 focus:bg-amber-50 focus:outline-none">No asistió</button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('citas.edit', $cita) }}" class="whitespace-nowrap rounded-md px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-100 focus:bg-gray-100 focus:outline-none">Reagendar</a>
                                                    <form method="POST" action="{{ route('citas.cancel', $cita) }}"
                                                          onsubmit="const motivo = prompt('Motivo de la cancelación (opcional):') ?? ''; if (!confirm('¿Cancelar esta cita?')) { return false; } this.querySelector('[name=motivo_cancelacion]').value = motivo; return true;">
                                                        @csrf
                                                        @method('patch')
                                                        <input type="hidden" name="motivo_cancelacion" value="">
                                                        <button type="submit" class="whitespace-nowrap rounded-md px-3 py-2 text-sm text-red-700 transition hover:bg-red-50 focus:bg-red-50 focus:outline-none">Cancelar</button>
                                                    </form>
                                                </div>
                                            </details>
                                        @elseif ($esPersonal && in_array($cita->estado->value, $estadosTerminales, true))
                                            <span class="text-sm text-gray-400">Sin acciones</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $esPersonal ? 6 : 5 }}" class="px-6 py-4 text-center text-gray-500">
                                    No hay citas registradas todavía.
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

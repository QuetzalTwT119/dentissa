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
$noFinalizable = ['completada', 'cancelada', 'no_asistio'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ $esPersonal ? 'Citas' : 'Mis citas' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
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

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
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

                <table class="w-full text-left text-sm">
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
                                <td class="px-6 py-4 text-gray-900">
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
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        @if ($esPersonal && $cita->estado->value === 'agendada')
                                            <form method="POST" action="{{ route('citas.confirm', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" title="Confirmar" aria-label="Confirmar cita"
                                                        class="rounded p-1.5 text-blue-600 transition hover:bg-blue-50 hover:text-blue-900">
                                                    <x-icon name="check" />
                                                </button>
                                            </form>
                                        @endif

                                        @if ($esPersonal && $cita->estado->value === 'confirmada')
                                            <form method="POST" action="{{ route('citas.start', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" title="Iniciar" aria-label="Iniciar cita"
                                                        class="rounded px-2 py-1 text-xs font-medium text-brand-700 transition hover:bg-brand-50 hover:text-brand-900">
                                                    Iniciar
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('citas.no-show', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" title="No asistió" aria-label="Marcar como no asistió"
                                                        class="rounded px-2 py-1 text-xs font-medium text-red-600 transition hover:bg-red-50 hover:text-red-900">
                                                    No asistió
                                                </button>
                                            </form>
                                        @endif

                                        @if ($esPersonal && $cita->estado->value === 'en_curso')
                                            <form method="POST" action="{{ route('citas.complete', $cita) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" title="Completar" aria-label="Completar cita"
                                                        class="rounded p-1.5 text-green-600 transition hover:bg-green-50 hover:text-green-900">
                                                    <x-icon name="check-circle" />
                                                </button>
                                            </form>
                                        @endif

                                        @unless (in_array($cita->estado->value, $noFinalizable))
                                            <a href="{{ route('citas.edit', $cita) }}" title="Reagendar" aria-label="Reagendar cita"
                                               class="rounded p-1.5 text-brand-600 transition hover:bg-brand-50 hover:text-brand-900">
                                                <x-icon name="calendar-clock" />
                                            </a>
                                        @endunless

                                        @unless (in_array($cita->estado->value, $noFinalizable))
                                            <form method="POST" action="{{ route('citas.cancel', $cita) }}"
                                                  onsubmit="const motivo = prompt('Motivo de la cancelación (opcional):') ?? ''; if (!confirm('¿Cancelar esta cita?')) { return false; } this.querySelector('[name=motivo_cancelacion]').value = motivo; return true;">
                                                @csrf
                                                @method('patch')
                                                <input type="hidden" name="motivo_cancelacion" value="">
                                                <button type="submit" title="Cancelar" aria-label="Cancelar cita"
                                                        class="rounded p-1.5 text-red-600 transition hover:bg-red-50 hover:text-red-900">
                                                    <x-icon name="x" />
                                                </button>
                                            </form>
                                        @endunless
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
</x-app-layout>

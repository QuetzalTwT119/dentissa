@php
$diaLabel = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Horarios de atención
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <p class="mb-6 text-sm text-gray-600">
                    Define para cada día si la clínica atiende, el horario y cuántas citas pueden coincidir al mismo tiempo.
                    Esto controla qué horarios ven los pacientes al agendar.
                </p>

                <form method="POST" action="{{ route('admin.horarios.update') }}">
                    @csrf
                    @method('put')

                    <table class="w-full text-left text-sm">
                        <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Día</th>
                                <th class="px-4 py-3">Abierto</th>
                                <th class="px-4 py-3">Apertura (hora)</th>
                                <th class="px-4 py-3">Cierre (hora)</th>
                                <th class="px-4 py-3">Capacidad simultánea</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($horarios as $indice => $horario)
                                <tr class="border-b border-gray-100" x-data="{ abierto: {{ old("dias.$indice.abierto", $horario->abierto) ? 'true' : 'false' }} }">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $diaLabel[$horario->dia_semana] }}</td>
                                    <td class="px-4 py-3">
                                        <input type="hidden" name="dias[{{ $indice }}][dia_semana]" value="{{ $horario->dia_semana }}">
                                        <x-checkbox name="dias[{{ $indice }}][abierto]" value="1" x-model="abierto" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-text-input type="number" min="0" max="23" class="w-20"
                                                      name="dias[{{ $indice }}][hora_apertura]"
                                                      value="{{ old("dias.$indice.hora_apertura", $horario->hora_apertura) }}"
                                                      x-bind:class="{ 'opacity-40 pointer-events-none': !abierto }" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-text-input type="number" min="0" max="23" class="w-20"
                                                      name="dias[{{ $indice }}][hora_cierre]"
                                                      value="{{ old("dias.$indice.hora_cierre", $horario->hora_cierre) }}"
                                                      x-bind:class="{ 'opacity-40 pointer-events-none': !abierto }" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <x-text-input type="number" min="1" class="w-20"
                                                      name="dias[{{ $indice }}][capacidad_simultanea]"
                                                      value="{{ old("dias.$indice.capacidad_simultanea", $horario->capacidad_simultanea) }}"
                                                      x-bind:class="{ 'opacity-40 pointer-events-none': !abierto }" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-6 flex items-center gap-4">
                        <x-primary-button>Guardar</x-primary-button>
                        @if (session('status') === 'horarios-actualizados')
                            <p class="text-sm text-gray-600">Guardado.</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

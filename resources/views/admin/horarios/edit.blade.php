@php
$diaLabel = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Configuración Operativa"
            title="Horarios de atención"
            subtitle="Disponibilidad semanal del consultorio y capacidad de citas simultáneas."
        />
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>
                <x-admin.card-header
                    title="Configuración de agenda semanal"
                    description="Define para cada día si la clínica atiende, el horario y cuántas citas pueden coincidir al mismo tiempo."
                />

                <form method="POST" action="{{ route('admin.horarios.update') }}">
                    @csrf
                    @method('put')

                    <x-admin.table minWidth="min-w-[640px]">
                        <x-slot:head>
                            <tr>
                                <th class="px-5 py-3.5">Día</th>
                                <th class="px-5 py-3.5">Abierto</th>
                                <th class="px-5 py-3.5">Apertura (hora)</th>
                                <th class="px-5 py-3.5">Cierre (hora)</th>
                                <th class="px-5 py-3.5">Capacidad simultánea</th>
                            </tr>
                        </x-slot:head>

                        @foreach ($horarios as $indice => $horario)
                            <tr class="transition hover:bg-[#FAF6F6]/40" x-data="{ abierto: {{ old("dias.$indice.abierto", $horario->abierto) ? 'true' : 'false' }} }">
                                <td class="px-5 py-3.5 font-semibold text-[#2D2526]">{{ $diaLabel[$horario->dia_semana] }}</td>
                                <td class="px-5 py-3.5">
                                    <input type="hidden" name="dias[{{ $indice }}][dia_semana]" value="{{ $horario->dia_semana }}">
                                    <x-checkbox name="dias[{{ $indice }}][abierto]" value="1" x-model="abierto" />
                                </td>
                                <td class="px-5 py-3.5">
                                    <x-text-input type="number" min="0" max="23" class="w-24 text-center"
                                                  name="dias[{{ $indice }}][hora_apertura]"
                                                  value="{{ old("dias.$indice.hora_apertura", $horario->hora_apertura) }}"
                                                  x-bind:class="{ 'opacity-40 pointer-events-none bg-gray-50': !abierto }" />
                                </td>
                                <td class="px-5 py-3.5">
                                    <x-text-input type="number" min="0" max="23" class="w-24 text-center"
                                                  name="dias[{{ $indice }}][hora_cierre]"
                                                  value="{{ old("dias.$indice.hora_cierre", $horario->hora_cierre) }}"
                                                  x-bind:class="{ 'opacity-40 pointer-events-none bg-gray-50': !abierto }" />
                                </td>
                                <td class="px-5 py-3.5">
                                    <x-text-input type="number" min="1" class="w-24 text-center"
                                                  name="dias[{{ $indice }}][capacidad_simultanea]"
                                                  value="{{ old("dias.$indice.capacidad_simultanea", $horario->capacidad_simultanea) }}"
                                                  x-bind:class="{ 'opacity-40 pointer-events-none bg-gray-50': !abierto }" />
                                </td>
                            </tr>
                        @endforeach
                    </x-admin.table>

                    <div class="flex items-center gap-4 p-6 border-t border-[#e9dfdb] bg-gradient-to-r from-white via-white to-[#FAF6F6]/50">
                        <x-primary-button>Guardar horarios</x-primary-button>
                        @if (session('status') === 'horarios-actualizados')
                            <p class="text-sm font-semibold text-emerald-700">Guardado correctamente.</p>
                        @endif
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

@php
$diaLabel = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Horarios de atención
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Disponibilidad semanal del consultorio y capacidad de citas simultáneas.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8">
                <p class="mb-6 text-sm text-[#6b5e62]">
                    Define para cada día si la clínica atiende, el horario y cuántas citas pueden coincidir al mismo tiempo.
                    Esto controla qué horarios ven los pacientes al agendar en el sitio web y con el asistente.
                </p>

                <form method="POST" action="{{ route('admin.horarios.update') }}">
                    @csrf
                    @method('put')

                    <div class="overflow-x-auto rounded-2xl border border-[#e9dfdb]">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
                            <tr>
                                <th class="px-5 py-3.5">Día</th>
                                <th class="px-5 py-3.5">Abierto</th>
                                <th class="px-5 py-3.5">Apertura (hora)</th>
                                <th class="px-5 py-3.5">Cierre (hora)</th>
                                <th class="px-5 py-3.5">Capacidad simultánea</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e9dfdb]/60">
                            @foreach ($horarios as $indice => $horario)
                                <tr class="transition hover:bg-[#FAF6F6]/40" x-data="{ abierto: {{ old("dias.$indice.abierto", $horario->abierto) ? 'true' : 'false' }} }">
                                    <td class="px-5 py-3.5 font-medium text-[#2D2526]">{{ $diaLabel[$horario->dia_semana] }}</td>
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
                        </tbody>
                    </table>
                    </div>

                    <div class="mt-6 flex items-center gap-4 pt-4 border-t border-[#e9dfdb]">
                        <x-primary-button>Guardar horarios</x-primary-button>
                        @if (session('status') === 'horarios-actualizados')
                            <p class="text-sm font-medium text-emerald-700">Guardado correctamente.</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

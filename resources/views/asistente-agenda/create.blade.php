<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Asistente de agenda
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-brand-100 bg-brand-50 p-5 text-sm text-brand-900">
                <p class="font-semibold">Primero propone; tú decides.</p>
                <p class="mt-1">El asistente solo consulta la disponibilidad. No guarda una cita ni envía un aviso hasta que pulses <em>Confirmar y agendar</em>.</p>
            </div>

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white p-6 shadow-sm sm:rounded-lg"
                 x-data="{
                    idServicio: @js(old('id_servicio', $propuesta['id_servicio'] ?? '')),
                    idTratamiento: @js(old('id_tratamiento', $propuesta['tratamiento']->id ?? '')),
                    servicios: @js($servicios),
                    get servicioSeleccionado() {
                        return this.servicios.find(servicio => servicio.id === Number(this.idServicio)) ?? null;
                    },
                 }">
                <form method="POST" action="{{ route('asistente-agenda.proponer') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="id_paciente" value="Paciente" />
                        <select id="id_paciente" name="id_paciente" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Selecciona un paciente</option>
                            @foreach ($pacientes as $paciente)
                                <option value="{{ $paciente->id }}" @selected(old('id_paciente', $propuesta['paciente']->id ?? null) == $paciente->id)>
                                    {{ $paciente->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="id_servicio" value="Servicio" />
                        <select id="id_servicio" name="id_servicio" required x-model="idServicio" @change="idTratamiento = ''"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Selecciona un servicio</option>
                            @foreach ($servicios as $servicio)
                                <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <template x-if="servicioSeleccionado">
                        <div>
                            <x-input-label for="id_tratamiento" value="Tratamiento" />
                            <select id="id_tratamiento" name="id_tratamiento" required x-model="idTratamiento"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Selecciona un tratamiento</option>
                                <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                    <option :value="tratamiento.id" x-text="tratamiento.nombre + ' (' + tratamiento.duracion_estimada + ' min)'"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    <div>
                        <x-input-label for="fecha" value="Fecha deseada" />
                        <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" required
                                      min="{{ now()->toDateString() }}" value="{{ old('fecha', $propuesta['fecha'] ?? '') }}" />
                    </div>

                    <div>
                        <x-input-label for="motivo" value="Motivo (opcional)" />
                        <textarea id="motivo" name="motivo" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('motivo', $propuesta['motivo'] ?? '') }}</textarea>
                    </div>

                    <x-primary-button>Buscar horarios y preparar propuesta</x-primary-button>
                </form>
            </div>

            @isset($propuesta)
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Propuesta para {{ $propuesta['paciente']->nombre_completo }}</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ $propuesta['tratamiento']->nombre }} · {{ $propuesta['tratamiento']->duracion_estimada }} minutos · {{ \Illuminate\Support\Carbon::parse($propuesta['fecha'])->translatedFormat('l d \d\e F') }}
                    </p>

                    @if (count($propuesta['horarios']))
                        <form method="POST" action="{{ route('citas.store') }}" class="mt-6 space-y-5">
                            @csrf
                            <input type="hidden" name="id_paciente" value="{{ $propuesta['paciente']->id }}">
                            <input type="hidden" name="id_servicio" value="{{ $propuesta['id_servicio'] }}">
                            <input type="hidden" name="id_tratamiento" value="{{ $propuesta['tratamiento']->id }}">
                            <input type="hidden" name="fecha" value="{{ $propuesta['fecha'] }}">
                            <input type="hidden" name="motivo" value="{{ $propuesta['motivo'] }}">

                            <div>
                                <x-input-label for="hora" value="Elige el horario que deseas confirmar" />
                                <select id="hora" name="hora" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @foreach ($propuesta['horarios'] as $horario)
                                        <option value="{{ $horario }}">{{ $horario }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>Confirmar y agendar</x-primary-button>
                                <a href="{{ route('asistente-agenda.create') }}"><x-secondary-button type="button">Empezar de nuevo</x-secondary-button></a>
                            </div>
                        </form>
                    @else
                        <p class="mt-4 rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">No hay horarios disponibles para esa fecha. Puedes buscar otra fecha sin modificar ninguna cita.</p>
                    @endif
                </div>
            @endisset
        </div>
    </div>
</x-app-layout>

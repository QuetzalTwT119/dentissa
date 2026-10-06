<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Agendar cita
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg"
                 x-data="{
                    idServicio: @js(old('id_servicio', '')),
                    idTratamiento: @js(old('id_tratamiento', '')),
                    fecha: @js(old('fecha', '')),
                    hora: @js(old('hora', null)),
                    horarios: [],
                    loadingHorarios: false,
                    servicios: @js($servicios),
                    get servicioSeleccionado() {
                        return this.servicios.find(s => s.id === Number(this.idServicio)) ?? null;
                    },
                    async cargarHorarios() {
                        this.hora = null;
                        if (!this.idTratamiento || !this.fecha) { this.horarios = []; return; }
                        this.loadingHorarios = true;
                        this.horarios = await window.cargarHorariosDisponibles(this.idTratamiento, this.fecha);
                        this.loadingHorarios = false;
                    },
                 }"
            >
                <form method="POST" action="{{ route('citas.store') }}" class="space-y-6">
                    @csrf

                    @if ($pacientes)
                        <div>
                            <x-input-label for="id_paciente" value="Paciente" />
                            <select id="id_paciente" name="id_paciente" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Selecciona un paciente</option>
                                @foreach ($pacientes as $paciente)
                                    <option value="{{ $paciente['id'] }}" @selected(old('id_paciente') == $paciente['id'])>{{ $paciente['nombre'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('id_paciente')" class="mt-2" />
                        </div>
                    @endif

                    <div>
                        <x-input-label for="id_servicio" value="Servicio" />
                        <select id="id_servicio" name="id_servicio" required
                                x-model="idServicio" @change="idTratamiento = ''"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Selecciona un servicio</option>
                            @foreach ($servicios as $servicio)
                                <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('id_servicio')" class="mt-2" />
                    </div>

                    <template x-if="servicioSeleccionado">
                        <div>
                            <x-input-label for="id_tratamiento" value="Tratamiento" />
                            <select id="id_tratamiento" name="id_tratamiento" required
                                    x-model="idTratamiento" @change="cargarHorarios()"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Selecciona un tratamiento</option>
                                <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                    <option :value="tratamiento.id" x-text="tratamiento.nombre + ' (' + tratamiento.duracion_estimada + ' min)'"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('id_tratamiento')" class="mt-2" />
                        </div>
                    </template>

                    <div>
                        <x-input-label for="fecha" value="Fecha" />
                        <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" required
                                      x-model="fecha" @change="cargarHorarios()" />
                        <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Horario disponible" class="mb-2" />
                        <input type="hidden" name="hora" x-model="hora">
                        @include('partials.disponibilidad-picker')
                        <x-input-error :messages="$errors->get('hora')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="motivo" value="Motivo (opcional)" />
                        <textarea id="motivo" name="motivo" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('motivo') }}</textarea>
                        <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button x-bind:disabled="!hora">Confirmar cita</x-primary-button>

                        <a href="{{ route('citas.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

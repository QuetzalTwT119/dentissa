<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Reagendar cita
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <div class="mb-6 rounded-md bg-gray-50 p-4 text-sm text-gray-700">
                    <p><span class="font-medium">Paciente:</span> {{ $cita->paciente->nombre }} {{ $cita->paciente->apellido }}</p>
                    <p><span class="font-medium">Servicio:</span> {{ $cita->servicio->nombre ?? '—' }}</p>
                    <p><span class="font-medium">Tratamiento:</span> {{ $cita->tratamiento->nombre ?? '—' }}</p>
                </div>

                <form method="POST" action="{{ route('citas.update', $cita) }}" class="space-y-6"
                      x-data="{
                        idTratamiento: {{ $cita->id_tratamiento }},
                        fecha: @js(old('fecha', \Illuminate\Support\Carbon::parse($cita->fecha_hora)->toDateString())),
                        hora: @js(old('hora', null)),
                        horarios: [],
                        loadingHorarios: false,
                        async cargarHorarios() {
                            this.hora = null;
                            if (!this.idTratamiento || !this.fecha) { this.horarios = []; return; }
                            this.loadingHorarios = true;
                            this.horarios = await window.cargarHorariosDisponibles(this.idTratamiento, this.fecha, {{ $cita->id }});
                            this.loadingHorarios = false;
                        },
                      }"
                      x-init="cargarHorarios()"
                >
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="fecha" value="Nueva fecha" />
                        <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" required
                                      x-model="fecha" @change="cargarHorarios()" />
                        <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Nuevo horario disponible" class="mb-2" />
                        <input type="hidden" name="hora" x-model="hora">
                        @include('partials.disponibilidad-picker')
                        <x-input-error :messages="$errors->get('hora')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button x-bind:disabled="!hora">Guardar nuevo horario</x-primary-button>

                        <a href="{{ route('citas.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

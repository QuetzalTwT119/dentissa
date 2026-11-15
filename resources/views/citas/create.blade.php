<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="eyebrow">Gestión de Agenda</p>
                <h2 class="mt-1.5 text-[1.7rem] leading-tight text-ink sm:text-3xl">
                    Agendar cita en consultorio
                </h2>
                <p class="mt-1 max-w-2xl text-sm text-muted">Registro directo de consulta médica para pacientes registrados.</p>
            </div>
            <a href="{{ route('citas.index') }}" class="btn-secondary hidden sm:inline-flex">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver a la agenda
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8"
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

                    <!-- Paso 1: Paciente -->
                    <div class="rounded-xl bg-cream/60 p-4 sm:p-5 border border-line/70 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-brand-600 flex items-center gap-1.5">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">1</span>
                                Selección de Paciente
                            </span>
                        </div>

                        @if ($pacientes)
                            <div>
                                <x-input-label for="id_paciente" value="Paciente" />
                                <select id="id_paciente" name="id_paciente" required
                                        class="mt-1.5 block w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm text-ink shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <option value="">Selecciona un paciente</option>
                                    @foreach ($pacientes as $paciente)
                                        <option value="{{ $paciente['id'] }}" @selected(old('id_paciente') == $paciente['id'])>{{ $paciente['nombre'] }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('id_paciente')" class="mt-2" />
                            </div>
                        @endif
                    </div>

                    <!-- Paso 2: Servicio y Tratamiento -->
                    <div class="rounded-xl bg-white p-4 sm:p-5 border border-line/70 space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 flex items-center gap-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">2</span>
                            Servicio y Procedimiento
                        </span>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="id_servicio" value="Servicio" />
                                <select id="id_servicio" name="id_servicio" required
                                        x-model="idServicio" @change="idTratamiento = ''"
                                        class="mt-1.5 block w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm text-ink shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
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
                                            class="mt-1.5 block w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm text-ink shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                        <option value="">Selecciona un tratamiento</option>
                                        <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                            <option :value="tratamiento.id" x-text="tratamiento.nombre + ' (' + tratamiento.duracion_estimada + ' min)'"></option>
                                        </template>
                                    </select>
                                    <x-input-error :messages="$errors->get('id_tratamiento')" class="mt-2" />
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Paso 3: Fecha y Horario -->
                    <div class="rounded-xl bg-white p-4 sm:p-5 border border-line/70 space-y-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-600 flex items-center gap-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">3</span>
                            Fecha y Horario
                        </span>

                        <div>
                            <x-input-label for="fecha" value="Fecha" />
                            <x-text-input id="fecha" name="fecha" type="date" class="mt-1.5 block w-full" required
                                          x-model="fecha" @change="cargarHorarios()" />
                            <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label value="Horario disponible" class="mb-2" />
                            <input type="hidden" name="hora" x-model="hora">
                            @include('partials.disponibilidad-picker')
                            <x-input-error :messages="$errors->get('hora')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Motivo -->
                    <div>
                        <x-input-label for="motivo" value="Motivo (opcional)" />
                        <textarea id="motivo" name="motivo" rows="3" placeholder="Notas clínicas o motivo específico de consulta..."
                                  class="mt-1.5 block w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm text-ink shadow-sm transition placeholder:text-muted/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('motivo') }}</textarea>
                        <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
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

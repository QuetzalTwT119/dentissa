<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Gestión de Agenda"
            title="Agendar cita en consultorio"
            subtitle="Registro directo de consulta médica para pacientes registrados."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8"
                 x-data="{
                    idServicio: @js(old('id_servicio', '')),
                    idTratamiento: @js(old('id_tratamiento', '')),
                    {{-- ?fecha=AAAA-MM-DD llega desde los horarios libres de la vista semanal. --}}
                    fecha: @js(old('fecha', preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) request()->query('fecha')) ? request()->query('fecha') : '')),
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
                <form method="POST" action="{{ route('citas.store') }}" data-confirmar="Se guardará y quedará disponible en el panel." data-confirmar-titulo="¿Crear la cita?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" class="formulario-panel formulario-cita space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    <!-- Paso 1: Paciente -->
                    <div class="rounded-xl bg-cream/60 p-4 sm:p-5 border border-line/70 space-y-4 lg:space-y-3 lg:p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-label text-brand-600 flex items-center gap-1.5">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-caption font-semibold text-brand-700">1</span>
                                Selección de Paciente
                            </span>
                        </div>

                        @if ($pacientes)
                            <div>
                                <x-input-label :required="true" for="id_paciente" value="Paciente" />
                                <x-select id="id_paciente" name="id_paciente" required class="mt-1.5">
                                    <option value="">Selecciona un paciente</option>
                                    @foreach ($pacientes as $paciente)
                                        <option value="{{ $paciente['id'] }}" @selected(old('id_paciente') == $paciente['id'])>{{ $paciente['nombre'] }}</option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('id_paciente')" class="mt-2" />
                                @if (auth()->user()->puede('directorio_pacientes'))
                                    {{-- 25/11/2026: antes no había forma de llegar desde aquí a registrar a alguien nuevo. --}}
                                    <p class="mt-2 text-sm text-ink-soft" data-nuevo-paciente>
                                        ¿No está en la lista?
                                        <a href="{{ route('admin.pacientes.create') }}" class="font-semibold text-brand-700 underline-offset-2 hover:underline">Registrar paciente nuevo</a>
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Paso 2: Servicio y Tratamiento -->
                    <div class="rounded-xl bg-white p-4 sm:p-5 border border-line/70 space-y-4 lg:space-y-3 lg:p-4">
                        <span class="text-xs font-semibold uppercase tracking-label text-brand-600 flex items-center gap-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-caption font-semibold text-brand-700">2</span>
                            Servicio y Procedimiento
                        </span>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label :required="true" for="id_servicio" value="Servicio" />
                                <x-select id="id_servicio" name="id_servicio" required x-model="idServicio" x-on:change="idTratamiento = ''" class="mt-1.5">
                                    <option value="">Selecciona un servicio</option>
                                    @foreach ($servicios as $servicio)
                                        <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                                    @endforeach
                                </x-select>
                                <x-input-error :messages="$errors->get('id_servicio')" class="mt-2" />
                            </div>

                            <template x-if="servicioSeleccionado">
                                <div>
                                    <x-input-label :required="true" for="id_tratamiento" value="Tratamiento" />
                                    <x-select id="id_tratamiento" name="id_tratamiento" required x-model="idTratamiento" x-on:change="cargarHorarios()" class="mt-1.5">
                                        <option value="">Selecciona un tratamiento</option>
                                        <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                            <option :value="tratamiento.id" x-text="tratamiento.nombre + ' (' + tratamiento.duracion_estimada + ' min)'"></option>
                                        </template>
                                    </x-select>
                                    <x-input-error :messages="$errors->get('id_tratamiento')" class="mt-2" />
                                    {{-- 25/11/2026: una especialidad sin tratamientos dejaba la lista vacía sin explicar por qué. --}}
                                    <p x-show="servicioSeleccionado.tratamientos.length === 0" x-cloak class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800" data-sin-tratamientos>
                                        Esta especialidad todavía no tiene tratamientos publicados, así que no se puede agendar en línea. Agrégalos en Catálogo → Servicios, o elige otra especialidad.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Paso 3: Fecha y Horario (en PC, columna derecha junto a paciente, servicio y motivo) -->
                    <div class="col-fecha rounded-xl bg-white p-4 sm:p-5 border border-line/70 space-y-4 lg:space-y-3 lg:p-4">
                        <span class="text-xs font-semibold uppercase tracking-label text-brand-600 flex items-center gap-1.5">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100 text-caption font-semibold text-brand-700">3</span>
                            Fecha y Horario
                        </span>

                        <div>
                            <input type="hidden" name="fecha" x-model="fecha">
                            <input type="hidden" name="hora" x-model="hora">
                            @include('partials.disponibilidad-picker')
                            <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                            <x-input-error :messages="$errors->get('hora')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Motivo -->
                    <div>
                        <x-input-label for="motivo" value="Motivo (opcional)" />
                        <x-textarea id="motivo" name="motivo" rows="3" placeholder="Notas clínicas o motivo específico de consulta..." class="mt-1.5">{{ old('motivo') }}</x-textarea>
                        <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button x-bind:disabled="!hora">Confirmar cita</x-primary-button>

                        <x-boton-cancelar href="{{ route('citas.index') }}" />
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

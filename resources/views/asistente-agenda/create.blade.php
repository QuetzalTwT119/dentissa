<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Asistente de agenda"
        />
    </x-slot>

    <div class="panel-pagina py-10">
        <div class="panel-contenido mx-auto max-w-5xl space-y-6 sm:px-6 lg:space-y-4 lg:px-8">
            <div class="rounded-2xl border border-brand-200/80 bg-brand-50/70 p-5 text-sm text-brand-900 shadow-2xs lg:px-5 lg:py-3">
                <p class="font-semibold text-brand-700">Primero propone; tú decides.</p>
                <p class="mt-1 text-ink-soft">El asistente solo consulta la disponibilidad. No guarda una cita ni envía un aviso hasta que pulses <em>Confirmar y agendar</em>.</p>
            </div>

            @if ($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8"
                 x-data="{
                    idServicio: @js(old('id_servicio', $propuesta['id_servicio'] ?? '')),
                    idTratamiento: @js(old('id_tratamiento', $propuesta['tratamiento']->id ?? '')),
                    servicios: @js($servicios),
                    get servicioSeleccionado() {
                        return this.servicios.find(servicio => servicio.id === Number(this.idServicio)) ?? null;
                    },
                 }">
                <form method="POST" action="{{ route('asistente-agenda.proponer') }}" class="formulario-panel space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    <div>
                        <x-input-label :required="true" for="id_paciente" value="Paciente" />
                        <x-select id="id_paciente" name="id_paciente" required class="mt-1.5">
                            <option value="">Selecciona un paciente</option>
                            @foreach ($pacientes as $paciente)
                                <option value="{{ $paciente->id }}" @selected(old('id_paciente', $propuesta['paciente']->id ?? null) == $paciente->id)>
                                    {{ $paciente->nombre_completo }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>

                    <div>
                        <x-input-label :required="true" for="id_servicio" value="Servicio" />
                        <x-select id="id_servicio" name="id_servicio" required x-model="idServicio" x-on:change="idTratamiento = ''" class="mt-1.5">
                            <option value="">Selecciona un servicio</option>
                            @foreach ($servicios as $servicio)
                                <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <template x-if="servicioSeleccionado">
                        <div>
                            <x-input-label :required="true" for="id_tratamiento" value="Tratamiento" />
                            <x-select id="id_tratamiento" name="id_tratamiento" required x-model="idTratamiento" class="mt-1.5">
                                <option value="">Selecciona un tratamiento</option>
                                <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                    <option :value="tratamiento.id" x-text="tratamiento.nombre + ' (' + tratamiento.duracion_estimada + ' min)'"></option>
                                </template>
                            </x-select>
                        </div>
                    </template>

                    <div>
                        <x-input-label :required="true" for="fecha" value="Fecha deseada" />
                        <x-text-input id="fecha" name="fecha" type="date" class="mt-1.5 block w-full" required
                                      min="{{ now()->toDateString() }}" value="{{ old('fecha', $propuesta['fecha'] ?? '') }}" />
                    </div>

                    <div>
                        <x-input-label for="motivo" value="Motivo (opcional)" />
                        <x-textarea id="motivo" name="motivo" rows="3" class="mt-1.5">{{ old('motivo', $propuesta['motivo'] ?? '') }}</x-textarea>
                    </div>

                    <x-primary-button>Buscar horarios y preparar propuesta</x-primary-button>
                </form>
            </div>

            @isset($propuesta)
                <x-admin.card class="p-5 sm:p-8">
                    <h3 class="text-headline font-semibold text-ink">Propuesta para {{ $propuesta['paciente']->nombre_completo }}</h3>
                    <p class="mt-1 text-sm text-ink-soft">
                        {{ $propuesta['tratamiento']->nombre }} · {{ $propuesta['tratamiento']->duracion_estimada }} minutos · {{ \Illuminate\Support\Carbon::parse($propuesta['fecha'])->translatedFormat('l d \d\e F') }}
                    </p>

                    @if (count($propuesta['horarios']))
                        <form method="POST" action="{{ route('citas.store') }}" data-confirmar="Se guardará y quedará disponible en el panel." data-confirmar-titulo="¿Crear la cita?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" class="mt-6 space-y-5">
                            @csrf
                            <x-form-obligatorios />
                            <input type="hidden" name="id_paciente" value="{{ $propuesta['paciente']->id }}">
                            <input type="hidden" name="id_servicio" value="{{ $propuesta['id_servicio'] }}">
                            <input type="hidden" name="id_tratamiento" value="{{ $propuesta['tratamiento']->id }}">
                            <input type="hidden" name="fecha" value="{{ $propuesta['fecha'] }}">
                            <input type="hidden" name="motivo" value="{{ $propuesta['motivo'] }}">

                            <div>
                                <x-input-label :required="true" for="hora" value="Elige el horario que deseas confirmar" />
                                <x-select id="hora" name="hora" required class="mt-1.5">
                                    @foreach ($propuesta['horarios'] as $horario)
                                        <option value="{{ $horario }}">{{ $horario }}</option>
                                    @endforeach
                                </x-select>
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>Confirmar y agendar</x-primary-button>
                                <a href="{{ route('asistente-agenda.create') }}"><x-secondary-button type="button">Empezar de nuevo</x-secondary-button></a>
                            </div>
                        </form>
                    @else
                        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs font-medium text-amber-800">No hay horarios disponibles para esa fecha. Puedes buscar otra fecha sin modificar ninguna cita.</p>
                    @endif
                </x-admin.card>
            @endisset
        </div>
    </div>
</x-app-layout>

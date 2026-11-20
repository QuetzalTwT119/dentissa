<x-app-layout>
    <x-slot name="header">
        <h1 class="mt-1.5 text-title-2 text-ink">
            Asistente de agenda
        </h1>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-brand-200/80 bg-brand-50/70 p-5 text-sm text-brand-900 shadow-2xs">
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
                <form method="POST" action="{{ route('asistente-agenda.proponer') }}" class="space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    <div>
                        <x-input-label :required="true" for="id_paciente" value="Paciente" />
                        <select id="id_paciente" name="id_paciente" required
                                class="min-h-11 mt-1.5 block w-full rounded-xl border-field text-sm shadow-2xs focus:border-brand-500 focus:ring-brand-400">
                            <option value="">Selecciona un paciente</option>
                            @foreach ($pacientes as $paciente)
                                <option value="{{ $paciente->id }}" @selected(old('id_paciente', $propuesta['paciente']->id ?? null) == $paciente->id)>
                                    {{ $paciente->nombre_completo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label :required="true" for="id_servicio" value="Servicio" />
                        <select id="id_servicio" name="id_servicio" required x-model="idServicio" @change="idTratamiento = ''"
                                class="min-h-11 mt-1.5 block w-full rounded-xl border-field text-sm shadow-2xs focus:border-brand-500 focus:ring-brand-400">
                            <option value="">Selecciona un servicio</option>
                            @foreach ($servicios as $servicio)
                                <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <template x-if="servicioSeleccionado">
                        <div>
                            <x-input-label :required="true" for="id_tratamiento" value="Tratamiento" />
                            <select id="id_tratamiento" name="id_tratamiento" required x-model="idTratamiento"
                                    class="min-h-11 mt-1.5 block w-full rounded-xl border-field text-sm shadow-2xs focus:border-brand-500 focus:ring-brand-400">
                                <option value="">Selecciona un tratamiento</option>
                                <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                    <option :value="tratamiento.id" x-text="tratamiento.nombre + ' (' + tratamiento.duracion_estimada + ' min)'"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    <div>
                        <x-input-label :required="true" for="fecha" value="Fecha deseada" />
                        <x-text-input id="fecha" name="fecha" type="date" class="mt-1.5 block w-full" required
                                      min="{{ now()->toDateString() }}" value="{{ old('fecha', $propuesta['fecha'] ?? '') }}" />
                    </div>

                    <div>
                        <x-input-label for="motivo" value="Motivo (opcional)" />
                        <textarea id="motivo" name="motivo" rows="3" class="mt-1.5 block w-full rounded-xl border-field text-sm shadow-2xs focus:border-brand-500 focus:ring-brand-400">{{ old('motivo', $propuesta['motivo'] ?? '') }}</textarea>
                    </div>

                    <x-primary-button>Buscar horarios y preparar propuesta</x-primary-button>
                </form>
            </div>

            @isset($propuesta)
                <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8">
                    <h3 class="text-headline font-semibold text-ink">Propuesta para {{ $propuesta['paciente']->nombre_completo }}</h3>
                    <p class="mt-1 text-sm text-ink-soft">
                        {{ $propuesta['tratamiento']->nombre }} · {{ $propuesta['tratamiento']->duracion_estimada }} minutos · {{ \Illuminate\Support\Carbon::parse($propuesta['fecha'])->translatedFormat('l d \d\e F') }}
                    </p>

                    @if (count($propuesta['horarios']))
                        <form method="POST" action="{{ route('citas.store') }}" class="mt-6 space-y-5">
                            @csrf
                            <x-form-obligatorios />
                            <input type="hidden" name="id_paciente" value="{{ $propuesta['paciente']->id }}">
                            <input type="hidden" name="id_servicio" value="{{ $propuesta['id_servicio'] }}">
                            <input type="hidden" name="id_tratamiento" value="{{ $propuesta['tratamiento']->id }}">
                            <input type="hidden" name="fecha" value="{{ $propuesta['fecha'] }}">
                            <input type="hidden" name="motivo" value="{{ $propuesta['motivo'] }}">

                            <div>
                                <x-input-label :required="true" for="hora" value="Elige el horario que deseas confirmar" />
                                <select id="hora" name="hora" required class="min-h-11 mt-1.5 block w-full rounded-xl border-field text-sm shadow-2xs focus:border-brand-500 focus:ring-brand-400">
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
                        <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs font-medium text-amber-800">No hay horarios disponibles para esa fecha. Puedes buscar otra fecha sin modificar ninguna cita.</p>
                    @endif
                </div>
            @endisset
        </div>
    </div>
</x-app-layout>

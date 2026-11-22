{{--
    Bloque Horarios de atención de la página Configuración (22/11/2026; antes
    página propia). Lo guardado se usa en la agenda en línea, el estado
    "Abierto hoy…" y el pie de la portada (HorarioAtencion::lineasSemanales()).
--}}
@php
$diaLabel = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
@endphp
<section id="horarios" aria-labelledby="titulo-horarios" class="scroll-mt-24">
    <h2 id="titulo-horarios" class="text-title-3 text-ink">Horarios de atención</h2>
    <p class="mt-1 text-sm text-muted">Días y horas en que se pueden agendar citas. Se muestran a los pacientes en la portada.</p>
    <div class="mt-3">
        <x-admin.card>
            <form id="form-horarios" method="POST" action="{{ route('admin.horarios.update') }}" data-confirmar="Los cambios se aplicarán de inmediato en la agenda y en la portada." data-confirmar-titulo="¿Guardar los horarios?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal">
                @csrf
                @method('put')

                {{-- Encabezados solo en PC; en celular cada campo lleva su etiqueta. --}}
                <div class="hidden grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.4fr)_minmax(0,1fr)] gap-4 border-b border-line bg-cream/40 px-6 py-2.5 text-xs font-semibold uppercase tracking-label text-muted lg:grid">
                    <span>Día</span>
                    <span>Estado</span>
                    <span>Horario</span>
                    <span title="Cuántas citas pueden coincidir a la misma hora (por ejemplo, 2 si hay dos sillones o dos dentistas).">Citas a la vez</span>
                </div>

                <ul class="divide-y divide-line">
                    @foreach ($horarios as $indice => $horario)
                        <li class="grid grid-cols-1 items-center gap-3 px-6 py-3 transition hover:bg-cream/30 lg:py-1.5 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.4fr)_minmax(0,1fr)] lg:gap-4"
                            x-data="{ abierto: {{ old("dias.$indice.abierto", $horario->abierto) ? 'true' : 'false' }} }" data-dia-horario>
                            <input type="hidden" name="dias[{{ $indice }}][dia_semana]" value="{{ $horario->dia_semana }}">

                            <span class="font-semibold text-ink">{{ $diaLabel[$horario->dia_semana] }}</span>

                            {{-- Interruptor (mismo estilo que "Disponible para agendar" en Servicios). --}}
                            <label class="inline-flex min-h-11 w-fit lg:min-h-9 cursor-pointer items-center gap-3">
                                <input type="checkbox" name="dias[{{ $indice }}][abierto]" value="1" role="switch" class="peer sr-only" x-model="abierto"
                                       aria-label="{{ $diaLabel[$horario->dia_semana] }} abierto">
                                <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full bg-line transition-colors duration-200 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform after:duration-200 after:ease-out peer-checked:bg-sage-600 peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300 motion-reduce:after:transition-none"></span>
                                <span class="text-sm font-medium" :class="abierto ? 'text-sage-700' : 'text-muted'" x-text="abierto ? 'Abierto' : 'Cerrado'">{{ $horario->abierto ? 'Abierto' : 'Cerrado' }}</span>
                            </label>

                            {{-- Cerrado: las horas se atenúan pero se conservan para cuando se vuelva a abrir. --}}
                            <div :class="{ 'opacity-40 pointer-events-none': !abierto }">
                                <div class="flex items-center gap-2 text-sm text-ink-soft">
                                    <span>de</span>
                                    <x-text-input type="number" min="0" max="23" class="w-20 text-center lg:min-h-9 lg:py-1" aria-label="Hora de apertura del {{ $diaLabel[$horario->dia_semana] }}"
                                                  name="dias[{{ $indice }}][hora_apertura]"
                                                  :value="old('dias.'.$indice.'.hora_apertura', $horario->hora_apertura)" x-bind:tabindex="abierto ? 0 : -1" />
                                    <span>a</span>
                                    <x-text-input type="number" min="0" max="23" class="w-20 text-center lg:min-h-9 lg:py-1" aria-label="Hora de cierre del {{ $diaLabel[$horario->dia_semana] }}"
                                                  name="dias[{{ $indice }}][hora_cierre]"
                                                  :value="old('dias.'.$indice.'.hora_cierre', $horario->hora_cierre)" x-bind:tabindex="abierto ? 0 : -1" />
                                    <span>hrs</span>
                                </div>
                                <x-input-error :messages="$errors->get('dias.'.$indice.'.hora_cierre')" class="mt-1" />
                            </div>

                            <div class="flex items-center gap-2 text-sm text-ink-soft" :class="{ 'opacity-40 pointer-events-none': !abierto }">
                                <x-text-input type="number" min="1" class="w-20 text-center lg:min-h-9 lg:py-1" aria-label="Citas a la vez el {{ $diaLabel[$horario->dia_semana] }}"
                                              name="dias[{{ $indice }}][capacidad_simultanea]"
                                              :value="old('dias.'.$indice.'.capacidad_simultanea', $horario->capacidad_simultanea)" x-bind:tabindex="abierto ? 0 : -1" />
                                <span class="lg:hidden">citas a la vez</span>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <p class="border-t border-line bg-cream/30 px-6 py-2.5 text-xs text-muted">
                    <strong class="font-semibold text-ink-soft">Citas a la vez:</strong> cuántos pacientes se pueden atender a la misma hora. Usa 1 si hay un solo sillón o dentista; 2 si pueden atender a dos al mismo tiempo.
                </p>

                {{-- Guardar al final del bloque (antes arriba en PC; ahora hay dos bloques en la página). --}}
                <div class="flex justify-end border-t border-line p-4">
                    <x-primary-button class="w-full justify-center sm:w-auto">Guardar horarios</x-primary-button>
                </div>
            </form>
        </x-admin.card>
    </div>
</section>

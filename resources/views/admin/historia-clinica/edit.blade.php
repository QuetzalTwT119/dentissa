{{--
    Cuestionario de historia clínica (anamnesis), 22/11/2026. Uno por paciente;
    se llena la primera vez y se actualiza cuando algo cambia. Opciones y nombres
    en App\Models\HistoriaClinica. Nada es obligatorio: se guarda lo que se sepa.
--}}
@use('App\Models\HistoriaClinica')
@php
    $esNueva = ! $historia->exists;
    // La asistente no ve el expediente: regresa a la lista de historias (22/11/2026).
    $puedeExpediente = auth()->user()->puede('pacientes');
    $regreso = \App\Http\Controllers\Admin\HistoriaClinicaController::urlRegreso($paciente);
    $marcadas = fn (string $campo) => collect(old($campo, $historia->{$campo} ?? []))->all();
    $secciones = [
        ['antecedentes_familiares', 'Antecedentes familiares', 'Padres, hermanos o abuelos con…', HistoriaClinica::ANTECEDENTES_FAMILIARES, 'antecedentes_familiares_notas', 'Quién y detalles (opcional)'],
        ['antecedentes_personales', 'Antecedentes personales', 'El paciente tiene o tuvo…', HistoriaClinica::ANTECEDENTES_PERSONALES, 'antecedentes_personales_notas', 'Detalles, cirugías u hospitalizaciones (opcional)'],
        ['alergias', 'Alergias', 'Es alérgico a…', HistoriaClinica::ALERGIAS, 'alergias_otras', 'Otras alergias (opcional)'],
        ['antecedentes_dentales', 'Antecedentes dentales', 'Marca lo que aplique', HistoriaClinica::ANTECEDENTES_DENTALES, 'antecedentes_dentales_notas', 'Detalles (opcional)'],
        ['habitos', 'Hábitos', 'Marca lo que aplique', HistoriaClinica::HABITOS, 'habitos_notas', 'Detalles, por ejemplo cuánto fuma (opcional)'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <nav aria-label="Ruta" class="text-sm text-muted">
                    @if ($puedeExpediente)
                        <a href="{{ route('admin.pacientes.index') }}" class="font-semibold text-brand-700 hover:text-brand-800">Pacientes</a>
                        <span aria-hidden="true" class="mx-1.5">›</span>
                        <a href="{{ route('admin.pacientes.historial.index', $paciente) }}" class="font-semibold text-brand-700 hover:text-brand-800">Expediente</a>
                    @else
                        <a href="{{ route('admin.historias.index') }}" class="font-semibold text-brand-700 hover:text-brand-800">Historias clínicas</a>
                    @endif
                    <span aria-hidden="true" class="mx-1.5">›</span>
                    <span>Historia clínica</span>
                </nav>
                <h1 class="mt-1 truncate text-title-2 text-ink">Historia clínica — {{ $paciente->nombre_completo }}</h1>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ $regreso }}" class="hidden sm:inline-block">
                    <x-secondary-button type="button">Cancelar</x-secondary-button>
                </a>
                <x-primary-button form="form-historia" class="hidden lg:inline-flex">{{ $esNueva ? 'Guardar historia clínica' : 'Guardar cambios' }}</x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="panel-contenido pb-8 pt-4 lg:pt-3">
        <form id="form-historia" method="POST" action="{{ route('admin.pacientes.historia.update', $paciente) }}"
              data-confirmar="{{ $esNueva ? 'Se guardará en el expediente del paciente.' : 'Los cambios se aplicarán de inmediato.' }}"
              data-confirmar-titulo="{{ $esNueva ? '¿Guardar la historia clínica?' : '¿Guardar los cambios de la historia clínica?' }}"
              data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal"
              x-data="{ sexo: @js((string) old('sexo', $historia->sexo)) }"
              class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8" data-cuestionario-historia>
            @csrf
            @method('put')

            @if (! $esNueva && $historia->actualizadoPor)
                <p class="text-sm text-muted">Última actualización: {{ $historia->updated_at?->translatedFormat('j M Y, H:i') }} por {{ $historia->actualizadoPor->nombre }} {{ $historia->actualizadoPor->apellido }}.</p>
            @endif
            <p class="text-sm text-ink-soft">Ningún dato es obligatorio: llena lo que sepas y complétalo en la siguiente visita. Los datos de salud solo los ve el personal autorizado.</p>

            {{-- Datos generales --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="hc-generales">
                <h2 id="hc-generales" class="text-headline font-semibold text-ink">Datos generales</h2>
                <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <x-input-label for="fecha_nacimiento" value="Fecha de nacimiento" />
                        <x-text-input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="mt-1.5 block w-full" :max="now()->toDateString()"
                                      :value="old('fecha_nacimiento', $historia->fecha_nacimiento?->toDateString())" />
                        @if ($historia->edad() !== null)
                            <p class="mt-1 text-xs text-muted">{{ $historia->edad() }} años</p>
                        @endif
                        <x-input-error :messages="$errors->get('fecha_nacimiento')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="sexo" value="Sexo" />
                        <x-select id="sexo" name="sexo" class="mt-1.5" x-model="sexo">
                            <option value="">Sin indicar</option>
                            @foreach (HistoriaClinica::SEXOS as $valor => $nombre)
                                <option value="{{ $valor }}" @selected(old('sexo', $historia->sexo) === $valor)>{{ $nombre }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('sexo')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="ocupacion" value="Ocupación" />
                        <x-text-input id="ocupacion" name="ocupacion" class="mt-1.5 block w-full" maxlength="100" :value="old('ocupacion', $historia->ocupacion)" />
                        <x-input-error :messages="$errors->get('ocupacion')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="estado_civil" value="Estado civil" />
                        <x-select id="estado_civil" name="estado_civil" class="mt-1.5">
                            <option value="">Sin indicar</option>
                            @foreach (HistoriaClinica::ESTADOS_CIVILES as $valor => $nombre)
                                <option value="{{ $valor }}" @selected(old('estado_civil', $historia->estado_civil) === $valor)>{{ $nombre }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('estado_civil')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="tipo_sangre" value="Tipo de sangre" />
                        <x-select id="tipo_sangre" name="tipo_sangre" class="mt-1.5">
                            <option value="">No lo sabe</option>
                            @foreach (HistoriaClinica::TIPOS_SANGRE as $tipo)
                                <option value="{{ $tipo }}" @selected(old('tipo_sangre', $historia->tipo_sangre) === $tipo)>{{ $tipo }}</option>
                            @endforeach
                        </x-select>
                        <x-input-error :messages="$errors->get('tipo_sangre')" class="mt-2" />
                    </div>
                </div>
            </section>

            {{-- Contacto de emergencia y médico de cabecera (23/11/2026). --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="hc-contactos" data-seccion="contactos">
                <h2 id="hc-contactos" class="text-headline font-semibold text-ink">Contacto de emergencia y médico</h2>
                <p class="mt-0.5 text-sm text-muted">A quién llamar si algo pasa durante la consulta.</p>
                <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    @foreach ([
                        ['contacto_emergencia_nombre', 'Contacto de emergencia', 'text', 150, 'Nombre completo'],
                        ['contacto_emergencia_parentesco', 'Parentesco', 'text', 50, 'Por ejemplo: mamá, esposo'],
                        ['contacto_emergencia_telefono', 'Teléfono del contacto', 'tel', 20, '755 123 4567'],
                        ['medico_cabecera_nombre', 'Médico de cabecera', 'text', 150, 'Nombre del médico'],
                        ['medico_cabecera_telefono', 'Teléfono del médico', 'tel', 20, '755 123 4567'],
                    ] as [$campoDato, $etiquetaDato, $tipoDato, $maximoDato, $ejemploDato])
                        <div @class(['sm:col-span-2' => $campoDato === 'medico_cabecera_nombre'])>
                            <x-input-label :for="$campoDato" :value="$etiquetaDato" />
                            <x-text-input :id="$campoDato" :name="$campoDato" :type="$tipoDato" class="mt-1.5 block w-full" :maxlength="$maximoDato" :placeholder="$ejemploDato"
                                          :value="old($campoDato, $historia->{$campoDato})" />
                            <x-input-error :messages="$errors->get($campoDato)" class="mt-2" />
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Listas de "sí" con detalles --}}
            @foreach ($secciones as [$campo, $titulo, $ayuda, $opciones, $campoNotas, $etiquetaNotas])
                <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="hc-{{ $campo }}" data-seccion="{{ $campo }}">
                    <h2 id="hc-{{ $campo }}" class="text-headline font-semibold text-ink">{{ $titulo }}</h2>
                    <p class="mt-0.5 text-sm text-muted">{{ $ayuda }}</p>
                    <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($opciones as $valor => $nombre)
                            <label for="{{ $campo }}-{{ $valor }}" class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-line bg-cream/40 px-3 py-2.5 transition hover:border-peach-300 hover:bg-white has-[:checked]:border-brand-200 has-[:checked]:bg-brand-50/60">
                                <x-checkbox id="{{ $campo }}-{{ $valor }}" name="{{ $campo }}[]" value="{{ $valor }}" :checked="in_array($valor, $marcadas($campo), true)" />
                                <span class="text-sm font-medium text-ink">{{ $nombre }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get($campo.'.*')" class="mt-2" />
                    <div class="mt-4">
                        <x-input-label :for="$campoNotas" :value="$etiquetaNotas" />
                        <x-textarea :id="$campoNotas" :name="$campoNotas" rows="2" class="mt-1.5">{{ old($campoNotas, $historia->{$campoNotas}) }}</x-textarea>
                        <x-input-error :messages="$errors->get($campoNotas)" class="mt-2" />
                    </div>
                    @if ($campo === 'antecedentes_dentales')
                        <div class="mt-4 sm:max-w-sm">
                            <x-input-label for="ultima_visita_dentista" value="Última visita al dentista" />
                            <x-text-input id="ultima_visita_dentista" name="ultima_visita_dentista" class="mt-1.5 block w-full" maxlength="100" placeholder="Por ejemplo: hace 2 años"
                                          :value="old('ultima_visita_dentista', $historia->ultima_visita_dentista)" />
                        </div>
                    @endif
                </section>

                @if ($campo === 'alergias')
                    {{-- Medicamentos y, para mujeres, embarazo y lactancia. --}}
                    <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="hc-medicamentos">
                        <h2 id="hc-medicamentos" class="text-headline font-semibold text-ink">Medicamentos que toma actualmente</h2>
                        <x-textarea id="medicamentos_actuales" name="medicamentos_actuales" rows="2" class="mt-3" aria-labelledby="hc-medicamentos">{{ old('medicamentos_actuales', $historia->medicamentos_actuales) }}</x-textarea>
                        <x-input-error :messages="$errors->get('medicamentos_actuales')" class="mt-2" />

                        <div class="mt-4">
                            <x-input-label for="tratamiento_medico_actual" value="¿Está en tratamiento médico? ¿De qué?" />
                            <x-textarea id="tratamiento_medico_actual" name="tratamiento_medico_actual" rows="2" class="mt-1.5" placeholder="Por ejemplo: control de presión con su médico familiar">{{ old('tratamiento_medico_actual', $historia->tratamiento_medico_actual) }}</x-textarea>
                            <x-input-error :messages="$errors->get('tratamiento_medico_actual')" class="mt-2" />
                        </div>

                        <div x-show="sexo === 'femenino'" x-cloak class="mt-5 border-t border-line pt-4" data-embarazo>
                            <p class="text-sm font-semibold text-ink">Solo mujeres</p>
                            <div class="mt-2 flex flex-wrap gap-3">
                                @foreach (['embarazo' => 'Está embarazada', 'lactancia' => 'Está en lactancia'] as $valor => $nombre)
                                    <label for="{{ $valor }}" class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-line bg-cream/40 px-3 py-2.5 has-[:checked]:border-brand-200 has-[:checked]:bg-brand-50/60">
                                        <input type="hidden" name="{{ $valor }}" value="0">
                                        <x-checkbox id="{{ $valor }}" name="{{ $valor }}" value="1" :checked="(bool) old($valor, $historia->{$valor})" />
                                        <span class="text-sm font-medium text-ink">{{ $nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
            @endforeach

            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="hc-observaciones">
                <h2 id="hc-observaciones" class="text-headline font-semibold text-ink">Observaciones</h2>
                <x-textarea id="observaciones" name="observaciones" rows="3" class="mt-3" aria-labelledby="hc-observaciones">{{ old('observaciones', $historia->observaciones) }}</x-textarea>
                <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
            </section>

            {{-- Celular y tableta: guardar abajo. --}}
            <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center lg:hidden">
                <x-primary-button class="w-full justify-center sm:w-auto">{{ $esNueva ? 'Guardar historia clínica' : 'Guardar cambios' }}</x-primary-button>
                <a href="{{ $regreso }}" class="inline-flex min-h-11 items-center justify-center rounded-full text-sm font-semibold text-ink-soft hover:text-ink">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>

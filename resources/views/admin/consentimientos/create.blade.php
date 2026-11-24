{{--
    Nuevo consentimiento informado (24/11/2026). Los textos vienen de partida y
    se ajustan a cada caso; al guardar se imprime para firmarlo a mano.
--}}
<x-app-layout>
    <x-slot name="header">
        <x-admin.header :volver="route('admin.pacientes.historial.index', $paciente)" volver-texto="Volver al expediente"
                        title="Consentimiento informado — {{ $paciente->nombre_completo }}"
                        subtitle="Escribe lo que le explicaste; se imprime para firmarlo." />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-aviso-clinico :historia="$paciente->historiaClinica" />

            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.pacientes.consentimientos.store', $paciente) }}"
                      data-confirmar="Se guardará en su expediente y podrás imprimirlo." data-confirmar-titulo="¿Guardar el consentimiento?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal"
                      class="formulario-panel formulario-una-columna space-y-6" data-consentimiento>
                    @csrf
                    <x-form-obligatorios />

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label :required="true" for="procedimiento" value="Procedimiento" />
                            <x-text-input id="procedimiento" name="procedimiento" list="lista-tratamientos" maxlength="150" class="mt-1.5 block w-full" required
                                          :value="old('procedimiento')" placeholder="Extracción, endodoncia, resina…" autocomplete="off" />
                            <datalist id="lista-tratamientos">
                                @foreach ($tratamientos as $nombre)
                                    <option value="{{ $nombre }}"></option>
                                @endforeach
                            </datalist>
                            <x-input-error :messages="$errors->get('procedimiento')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="dientes" value="Dientes (opcional)" />
                            <x-text-input id="dientes" name="dientes" maxlength="100" class="mt-1.5 block w-full" :value="old('dientes')" placeholder="Por ejemplo: 36 y 46" />
                            <x-input-error :messages="$errors->get('dientes')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label :required="true" for="beneficios" value="Beneficios que se esperan" />
                        <x-textarea id="beneficios" name="beneficios" rows="3" required class="mt-1.5">{{ old('beneficios', \App\Models\Consentimiento::BENEFICIOS) }}</x-textarea>
                        <x-input-error :messages="$errors->get('beneficios')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label :required="true" for="riesgos" value="Riesgos y molestias posibles" />
                        <x-textarea id="riesgos" name="riesgos" rows="4" required class="mt-1.5">{{ old('riesgos', \App\Models\Consentimiento::RIESGOS) }}</x-textarea>
                        <x-input-error :messages="$errors->get('riesgos')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label :required="true" for="alternativas" value="Alternativas" />
                        <x-textarea id="alternativas" name="alternativas" rows="3" required class="mt-1.5">{{ old('alternativas', \App\Models\Consentimiento::ALTERNATIVAS) }}</x-textarea>
                        <x-input-error :messages="$errors->get('alternativas')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 border-t border-line pt-4">
                        <x-primary-button>Guardar consentimiento</x-primary-button>
                        <a href="{{ route('admin.pacientes.historial.index', $paciente) }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

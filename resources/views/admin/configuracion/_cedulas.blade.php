{{--
    Cédulas profesionales (26/11/2026; antes en Mi perfil). Una línea por cada
    doctor activo que atiende consultas. Sale en sus recetas y consentimientos en
    PDF; si está vacía, el PDF omite esa línea (salvo que DENTISSA_RECETA_EXIGE_CEDULA
    esté encendido). Pide confirmación y queda en Control de actividades.
--}}
<section id="cedulas" aria-labelledby="titulo-cedulas" class="scroll-mt-24" data-cedulas>
    <h2 id="titulo-cedulas" class="text-title-3 text-ink">Cédulas profesionales</h2>
    <p class="mt-1 text-sm text-muted">
        Salen en las recetas y los consentimientos en PDF de cada doctor.{{ config('dentissa.recetas.exigir_cedula') ? ' Sin ella, ese doctor no puede imprimir recetas.' : '' }}
    </p>
    <div class="mt-3">
        <x-admin.card class="p-5 sm:p-8">
            @if ($doctores->isEmpty())
                <p class="text-center text-sm text-muted" data-sin-doctores>No hay doctores activos.</p>
            @else
                <form method="POST" action="{{ route('admin.consultorio.cedulas') }}" class="formulario-panel space-y-4"
                      data-confirmar="La cédula saldrá en las recetas y consentimientos nuevos."
                      data-confirmar-titulo="¿Guardar las cédulas profesionales?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal">
                    @csrf
                    @method('put')

                    @foreach ($doctores as $doctor)
                        <div class="grid gap-1.5 sm:grid-cols-[minmax(0,1fr)_minmax(0,16rem)] sm:items-center sm:gap-4" data-cedula-doctor>
                            <label for="cedula-{{ $doctor->id }}" class="text-sm font-semibold text-ink">
                                {{ $doctor->nombre }} {{ $doctor->apellido }}
                                <span class="block text-xs font-normal text-muted">{{ $doctor->rol->etiqueta() }}</span>
                            </label>
                            <div>
                                <x-text-input id="cedula-{{ $doctor->id }}" name="cedulas[{{ $doctor->id }}]" type="text" inputmode="numeric" maxlength="20"
                                              class="block w-full" :value="old('cedulas.'.$doctor->id, $doctor->cedula_profesional)"
                                              placeholder="Número de cédula" autocomplete="off" />
                                <x-input-error :messages="$errors->get('cedulas.'.$doctor->id)" class="mt-2" />
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end border-t border-line pt-4">
                        <x-primary-button>Guardar cédulas</x-primary-button>
                    </div>
                </form>
            @endif
        </x-admin.card>
    </div>
</section>

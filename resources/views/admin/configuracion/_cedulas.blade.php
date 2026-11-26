{{--
    Cédulas profesionales (26/11/2026; antes en Mi perfil). Tabla con el mismo
    patrón que Horarios de atención: una fila por doctor activo que atiende
    consultas (la titular primero), con su campo y una etiqueta Capturada/Falta
    que cambia al escribir. Sale en sus recetas y consentimientos en PDF; si
    falta, el PDF omite esa línea (salvo DENTISSA_RECETA_EXIGE_CEDULA).
    Pide confirmación y queda en Control de actividades.
--}}
@php
    $faltan = $doctores->filter(fn ($doctor) => blank($doctor->cedula_profesional))->count();
@endphp
<section id="cedulas" aria-labelledby="titulo-cedulas" class="scroll-mt-24" data-cedulas>
    <h2 id="titulo-cedulas" class="text-title-3 text-ink">Cédulas profesionales</h2>
    <p class="mt-1 text-sm text-muted">
        Aparecen en las recetas y los consentimientos en PDF de cada doctor.{{ config('dentissa.recetas.exigir_cedula') ? ' Sin ella, ese doctor no puede imprimir recetas.' : '' }}
    </p>
    <div class="mt-3">
        <x-admin.card>
            @if ($doctores->isEmpty())
                <p class="px-6 py-8 text-center text-sm text-muted" data-sin-doctores>No hay doctores activos.</p>
            @else
                <form method="POST" action="{{ route('admin.consultorio.cedulas') }}"
                      data-confirmar="La cédula aparecerá en las recetas y consentimientos nuevos."
                      data-confirmar-titulo="¿Guardar las cédulas profesionales?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal">
                    @csrf
                    @method('put')

                    {{-- Encabezados solo en PC; en celular cada fila se apila. --}}
                    <div class="hidden grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_7.5rem] gap-4 border-b border-line bg-cream/40 px-6 py-2.5 text-xs font-semibold uppercase tracking-label text-muted lg:grid">
                        <span>Doctor</span>
                        <span>Cédula profesional</span>
                        <span>Estado</span>
                    </div>

                    <ul class="divide-y divide-line">
                        @foreach ($doctores as $doctor)
                            @php
                                $valor = old('cedulas.'.$doctor->id, $doctor->cedula_profesional);
                                $iniciales = mb_strtoupper(mb_substr($doctor->nombre, 0, 1).mb_substr($doctor->apellido, 0, 1));
                            @endphp
                            <li class="grid grid-cols-1 items-center gap-3 px-6 py-4 transition hover:bg-cream/30 sm:grid-cols-[minmax(0,1fr)_7.5rem] lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_7.5rem] lg:gap-4 lg:py-3"
                                x-data="{ cedula: @js((string) $valor) }" data-cedula-doctor>
                                {{-- Doctor: iniciales, nombre y rol. --}}
                                <div class="flex min-w-0 items-center gap-3 sm:col-span-2 lg:col-span-1">
                                    <span aria-hidden="true" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-brand-200/70">{{ $iniciales }}</span>
                                    <div class="min-w-0">
                                        <label for="cedula-{{ $doctor->id }}" class="block truncate font-semibold text-ink">{{ $doctor->nombre }} {{ $doctor->apellido }}</label>
                                        <p class="text-xs text-muted">{{ $doctor->rol->etiqueta() }}</p>
                                    </div>
                                </div>

                                <div>
                                    <x-text-input id="cedula-{{ $doctor->id }}" name="cedulas[{{ $doctor->id }}]" type="text" inputmode="numeric" maxlength="20"
                                                  class="block w-full" :value="$valor" x-model="cedula"
                                                  placeholder="Ej. 12345678" autocomplete="off" aria-describedby="cedulas-ayuda" />
                                    <x-input-error :messages="$errors->get('cedulas.'.$doctor->id)" class="mt-2" />
                                </div>

                                {{-- Estado: cambia al escribir. --}}
                                <div aria-live="polite">
                                    <span x-show="cedula.trim() !== ''" @if (blank($valor)) x-cloak @endif data-estado-cedula="capturada">
                                        <x-chip class="border border-sage-200 bg-sage-50 text-sage-700">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            Capturada
                                        </x-chip>
                                    </span>
                                    <span x-show="cedula.trim() === ''" @if (filled($valor)) x-cloak @endif data-estado-cedula="falta">
                                        <x-chip class="border border-amber-200 bg-amber-50 text-amber-800">Falta</x-chip>
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex flex-col gap-3 border-t border-line bg-cream/30 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p id="cedulas-ayuda" class="text-xs text-muted">
                            @if ($faltan > 0)
                                <span class="font-semibold text-amber-800" data-cedulas-faltan>{{ $faltan === 1 ? 'Falta 1 cédula.' : "Faltan {$faltan} cédulas." }}</span>
                            @endif
                            Solo números, letras y guiones, hasta 20 caracteres.
                        </p>
                        <x-primary-button class="justify-center">Guardar cédulas</x-primary-button>
                    </div>
                </form>
            @endif
        </x-admin.card>
    </div>
</section>

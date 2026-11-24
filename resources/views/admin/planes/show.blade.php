{{--
    Plan de tratamiento (24/11/2026): datos del plan, avance y pasos. Cada paso
    se marca como hecho con un clic confirmado; el avance se calcula solo.
--}}
@use('App\Models\Pago')
@use('App\Models\PlanTratamiento')
<x-app-layout>
    <x-slot name="header">
        <x-admin.header :volver="route('admin.pacientes.historial.index', $paciente)" volver-texto="Volver al expediente"
                        title="{{ $plan->titulo }} — {{ $paciente->nombre_completo }}" subtitle="Plan de tratamiento: pasos pendientes y hechos." />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Avance --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-label="Avance del plan" data-avance-plan>
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-label text-muted">Avance</p>
                        <p class="mt-1 text-title-2 text-ink" data-porcentaje>{{ $plan->avance() }} %</p>
                        <p class="text-sm text-muted">{{ $plan->pasos->whereNotNull('hecho_en')->count() }} de {{ $plan->pasos->count() }} {{ $plan->pasos->count() === 1 ? 'paso hecho' : 'pasos hechos' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-label text-muted">Costo del plan</p>
                        <p class="mt-1 text-headline font-semibold text-ink">{{ $plan->costoTotal() > 0 ? Pago::dinero($plan->costoTotal()) : '—' }}</p>
                    </div>
                </div>
                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-line" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $plan->avance() }}" aria-label="Avance del plan">
                    <div class="h-full rounded-full bg-sage-500" style="width: {{ $plan->avance() }}%"></div>
                </div>
            </section>

            {{-- Pasos --}}
            <x-admin.card id="pasos" class="scroll-mt-24">
                <h2 class="border-b border-line px-5 py-4 text-headline font-semibold text-ink sm:px-6">Pasos</h2>
                @if ($plan->pasos->isEmpty())
                    <p class="px-5 py-6 text-sm text-muted sm:px-6">Aún no tiene pasos. Agrega el primero abajo.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($plan->pasos as $paso)
                            <li class="flex flex-wrap items-center gap-3 px-5 py-3 sm:px-6" data-paso>
                                <form method="POST" action="{{ route('admin.pacientes.planes.pasos.alternar', [$paciente, $plan, $paso]) }}"
                                      data-confirmar="{{ $paso->hecho() ? 'Volverá a quedar pendiente.' : 'Se guarda con la fecha de hoy.' }}"
                                      data-confirmar-titulo="{{ $paso->hecho() ? '¿Marcar como pendiente?' : '¿Marcar como hecho?' }}"
                                      data-confirmar-boton="{{ $paso->hecho() ? 'Sí, pendiente' : 'Sí, hecho' }}" data-confirmar-tono="normal">
                                    @csrf
                                    @method('patch')
                                    <button type="submit" aria-label="{{ $paso->hecho() ? 'Marcar como pendiente' : 'Marcar como hecho' }}: {{ $paso->descripcion }}"
                                            @class(['grid h-11 w-11 place-items-center rounded-full border-2 transition lg:h-9 lg:w-9',
                                                    'border-sage-500 bg-sage-500 text-white hover:bg-sage-600' => $paso->hecho(),
                                                    'border-line bg-white text-transparent hover:border-sage-400 hover:text-sage-400' => ! $paso->hecho()])>
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                                    </button>
                                </form>
                                <div class="min-w-0 flex-1">
                                    <p @class(['font-semibold', 'text-muted line-through' => $paso->hecho(), 'text-ink' => ! $paso->hecho()])>{{ $paso->descripcion }}{{ $paso->dientes ? ' · '.$paso->dientes : '' }}</p>
                                    <p class="text-xs text-muted">
                                        {{ $paso->hecho() ? 'Hecho el '.$paso->hecho_en->translatedFormat('j M Y') : 'Pendiente' }}{{ $paso->costo !== null ? ' · '.Pago::dinero($paso->costo) : '' }}
                                    </p>
                                </div>
                                <x-admin.acciones-fila :eliminar="route('admin.pacientes.planes.pasos.quitar', [$paciente, $plan, $paso])" que="el paso" :nombre="$paso->descripcion" confirmar-titulo="¿Quitar «{{ $paso->descripcion }}» del plan?" />
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Agregar paso --}}
                <form method="POST" action="{{ route('admin.pacientes.planes.pasos.agregar', [$paciente, $plan]) }}" class="border-t border-line bg-cream/40 p-5 sm:p-6"
                      data-confirmar="Quedará como pendiente." data-confirmar-titulo="¿Agregar el paso al plan?" data-confirmar-boton="Sí, agregar" data-confirmar-tono="normal"
                      x-data="{ precios: @js($tratamientos->mapWithKeys(fn ($t) => [$t->nombre => (float) $t->precio_referencia])) }" data-agregar-paso>
                    @csrf
                    <p class="text-sm font-semibold text-ink">Agregar paso</p>
                    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                        <div>
                            <x-input-label :required="true" for="descripcion" value="Procedimiento" />
                            <x-text-input id="descripcion" name="descripcion" list="lista-procedimientos" maxlength="150" class="mt-1.5 block w-full" required :value="old('descripcion')"
                                          x-on:change="if (precios[$event.target.value] > 0 && ! $refs.costo.value) $refs.costo.value = precios[$event.target.value]" placeholder="Resina, endodoncia…" autocomplete="off" />
                            <datalist id="lista-procedimientos">
                                @foreach ($tratamientos as $tratamiento)
                                    <option value="{{ $tratamiento->nombre }}"></option>
                                @endforeach
                            </datalist>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="dientes" value="Dientes" />
                            <x-text-input id="dientes" name="dientes" maxlength="100" class="mt-1.5 block w-full" :value="old('dientes')" placeholder="36" />
                        </div>
                        <div>
                            <x-input-label for="costo" value="Costo (MXN)" />
                            <x-text-input id="costo" name="costo" type="number" min="0" step="0.01" inputmode="decimal" class="mt-1.5 block w-full" :value="old('costo')" x-ref="costo" />
                            <x-input-error :messages="$errors->get('costo')" class="mt-2" />
                        </div>
                        <x-primary-button class="justify-center">Agregar</x-primary-button>
                    </div>
                </form>
            </x-admin.card>

            {{-- Datos del plan --}}
            <x-admin.card class="p-5 sm:p-6">
                <h2 class="text-headline font-semibold text-ink">Datos del plan</h2>
                @if ($plan->doctor)
                    <p class="mt-0.5 text-sm text-muted">Lo creó {{ $plan->doctor->nombre }} {{ $plan->doctor->apellido }} el {{ $plan->created_at->translatedFormat('j M Y') }}</p>
                @endif
                <form method="POST" action="{{ route('admin.pacientes.planes.update', [$paciente, $plan]) }}" class="mt-4 space-y-4"
                      data-confirmar="Los cambios se aplicarán de inmediato." data-confirmar-titulo="¿Guardar el plan?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal">
                    @csrf
                    @method('put')
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                        <div>
                            <x-input-label :required="true" for="titulo" value="Nombre del plan" />
                            <x-text-input id="titulo" name="titulo" maxlength="120" class="mt-1.5 block w-full" required :value="old('titulo', $plan->titulo)" />
                            <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label :required="true" for="estado" value="Estado" />
                            <x-select id="estado" name="estado" class="mt-1.5">
                                @foreach (PlanTratamiento::ESTADOS as $clave => $texto)
                                    <option value="{{ $clave }}" @selected(old('estado', $plan->estado) === $clave)>{{ $texto }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <div>
                        <x-input-label for="notas_plan" value="Notas (opcional)" />
                        <x-textarea id="notas_plan" name="notas" rows="3" class="mt-1.5">{{ old('notas', $plan->notas) }}</x-textarea>
                    </div>
                    <div class="flex justify-end">
                        <x-primary-button>Guardar cambios</x-primary-button>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

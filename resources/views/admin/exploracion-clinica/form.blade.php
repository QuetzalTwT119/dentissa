{{--
    Exploración clínica odontológica (23/11/2026), nueva o editar. La llena la
    doctora (módulo "pacientes"). Secciones del formato de historia clínica
    odontológica: examen de la boca, ATM, encías, higiene (O'Leary), problemas
    por diente, radiografías, odontograma y diagnósticos.
--}}
@use('App\Models\ExploracionClinica')
@php
    $esNueva = ! $exploracion->exists;
    $accion = $esNueva
        ? route('admin.pacientes.exploracion.store', $paciente)
        : route('admin.pacientes.exploracion.update', [$paciente, $exploracion]);
    $estomatologico = old('estomatologico', $exploracion->estomatologico ?? []);
    $periodontal = old('periodontal', $exploracion->periodontal ?? []);
    $filas = fn (string $campo, array $vacia) => array_values(old($campo, $exploracion->{$campo} ?? [])) ?: [$vacia];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="truncate text-title-2 text-ink">Exploración clínica — {{ $paciente->nombre_completo }}</h1>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.pacientes.historial.index', $paciente) }}" class="hidden sm:inline-block">
                    <x-secondary-button type="button">Cancelar</x-secondary-button>
                </a>
                <x-primary-button form="form-exploracion" class="hidden lg:inline-flex">{{ $esNueva ? 'Guardar exploración' : 'Guardar cambios' }}</x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <x-aviso-clinico :historia="$paciente->historiaClinica" class="mx-auto mb-6 max-w-5xl px-4 sm:px-6 lg:px-8" />

        <form id="form-exploracion" method="POST" action="{{ $accion }}"
              data-confirmar="{{ $esNueva ? 'Se guardará en el expediente del paciente.' : 'Los cambios se aplicarán de inmediato.' }}"
              data-confirmar-titulo="{{ $esNueva ? '¿Guardar la exploración clínica?' : '¿Guardar los cambios de la exploración?' }}"
              data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal"
              class="panel-contenido mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8" data-exploracion-clinica>
            @csrf
            @unless ($esNueva)
                @method('put')
            @endunless

            <p class="text-sm text-ink-soft">
                Solo la fecha es obligatoria: llena lo que revisaste hoy.
                @if ($basadaEn)
                    El odontograma viene de la exploración del {{ $basadaEn->fecha->translatedFormat('j M Y') }}; actualiza solo lo que cambió.
                @endif
            </p>

            {{-- Fecha --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6">
                <div class="sm:max-w-xs">
                    <x-input-label :required="true" for="fecha" value="Fecha de la exploración" />
                    <x-text-input id="fecha" name="fecha" type="date" class="mt-1.5 block w-full" required :max="now()->toDateString()"
                                  :value="old('fecha', $exploracion->fecha?->toDateString())" />
                    <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                </div>
            </section>

            {{-- 1. Examen estomatológico --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="ex-estomatologico" data-seccion="estomatologico">
                <h2 id="ex-estomatologico" class="text-headline font-semibold text-ink">Examen de la boca (estomatológico)</h2>
                <p class="mt-0.5 text-sm text-muted">Marca "Normal" o escribe lo que encontraste.</p>
                <div class="mt-4 divide-y divide-line rounded-2xl border border-line">
                    @foreach (ExploracionClinica::ESTRUCTURAS as $clave => $nombre)
                        <div class="grid grid-cols-1 items-center gap-2 px-4 py-3 sm:grid-cols-[12rem_6rem_minmax(0,1fr)]">
                            <span class="text-sm font-semibold text-ink">{{ $nombre }}</span>
                            <label for="est-{{ $clave }}" class="flex min-h-11 cursor-pointer items-center gap-2 text-sm text-ink-soft">
                                <x-checkbox id="est-{{ $clave }}" name="estomatologico[{{ $clave }}][normal]" value="1" :checked="(bool) ($estomatologico[$clave]['normal'] ?? false)" />
                                Normal
                            </label>
                            <x-text-input name="estomatologico[{{ $clave }}][hallazgo]" class="block w-full" maxlength="300" placeholder="Hallazgo (opcional)"
                                          aria-label="Hallazgo en {{ $nombre }}" :value="$estomatologico[$clave]['hallazgo'] ?? ''" />
                        </div>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('estomatologico.*')" class="mt-2" />
            </section>

            {{-- 2. ATM --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="ex-atm">
                <h2 id="ex-atm" class="text-headline font-semibold text-ink">Articulación de la mandíbula (ATM)</h2>
                <p class="mt-0.5 text-sm text-muted">Dolor, chasquidos, desviación o límite al abrir.</p>
                <x-textarea id="atm" name="atm" rows="2" class="mt-3" aria-labelledby="ex-atm">{{ old('atm', $exploracion->atm) }}</x-textarea>
                <x-input-error :messages="$errors->get('atm')" class="mt-2" />
            </section>

            {{-- 3. Encías --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="ex-periodontal" data-seccion="periodontal">
                <h2 id="ex-periodontal" class="text-headline font-semibold text-ink">Encías (periodontal)</h2>
                <p class="mt-0.5 text-sm text-muted">Escribe en qué dientes se presenta; deja vacío lo que no aplica.</p>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach (ExploracionClinica::PERIODONTAL as $clave => $nombre)
                        <div>
                            <x-input-label for="per-{{ $clave }}" :value="$nombre" />
                            <x-text-input id="per-{{ $clave }}" name="periodontal[{{ $clave }}]" class="mt-1.5 block w-full" maxlength="100" placeholder="Dientes, por ejemplo: 16, 17 o «todos»"
                                          :value="$periodontal[$clave] ?? ''" />
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- 4. Higiene oral --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="ex-higiene"
                     x-data="{ dientes: @js((string) old('oleary_dientes', $exploracion->oleary_dientes)), placa: @js((string) old('oleary_superficies_placa', $exploracion->oleary_superficies_placa)),
                               get indice() { const d = Number(this.dientes), p = Number(this.placa); return d > 0 && this.placa !== '' ? Math.min(100, Math.round(p / (d * 4) * 1000) / 10) : null; } }">
                <h2 id="ex-higiene" class="text-headline font-semibold text-ink">Higiene oral</h2>
                <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div>
                        <x-input-label for="higiene_oral" value="Higiene oral" />
                        <x-select id="higiene_oral" name="higiene_oral" class="mt-1.5">
                            <option value="">Sin revisar</option>
                            @foreach (ExploracionClinica::HIGIENE as $valor => $nombre)
                                <option value="{{ $valor }}" @selected(old('higiene_oral', $exploracion->higiene_oral) === $valor)>{{ $nombre }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div>
                        <x-input-label for="oleary_dientes" value="Dientes presentes" />
                        <x-text-input id="oleary_dientes" name="oleary_dientes" type="number" min="1" max="52" class="mt-1.5 block w-full" x-model="dientes" />
                        <x-input-error :messages="$errors->get('oleary_dientes')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="oleary_superficies_placa" value="Superficies con placa" />
                        <x-text-input id="oleary_superficies_placa" name="oleary_superficies_placa" type="number" min="0" max="208" class="mt-1.5 block w-full" x-model="placa" />
                        <x-input-error :messages="$errors->get('oleary_superficies_placa')" class="mt-2" />
                    </div>
                </div>
                <p class="mt-4 rounded-2xl bg-cream px-4 py-3 text-sm text-ink-soft" data-oleary role="status">
                    <span class="font-semibold text-ink">Índice de placa de O'Leary:</span>
                    <span x-text="indice === null ? 'llena dientes y superficies' : `${indice} %`">—</span>
                    <span x-show="indice !== null" x-cloak :class="indice <= 20 ? 'text-sage-700' : 'text-rose-700'"
                          x-text="indice <= 20 ? '· aceptable (20 % o menos)' : '· alto (más de 20 %)'"></span>
                    <span class="block text-xs text-muted">Superficies con placa ÷ (dientes × 4) × 100.</span>
                </p>
            </section>

            {{-- 5 y 6. Problemas por diente: pulpares y radiografías --}}
            {{-- Clases completas (Tailwind no genera clases armadas con variables). --}}
            @foreach ([
                ['pulpares', 'Problemas de la pulpa (por diente)', 'Signos y síntomas, e impresión diagnóstica.', [['signos', 'Signos y síntomas'], ['diagnostico', 'Impresión diagnóstica']], 'sm:grid-cols-[5.5rem_minmax(0,1fr)_minmax(0,1fr)_2.75rem]'],
                ['radiografica', 'Interpretación radiográfica', 'Lo que se ve en la radiografía de cada diente.', [['hallazgo', 'Hallazgo']], 'sm:grid-cols-[5.5rem_minmax(0,1fr)_2.75rem]'],
            ] as [$campo, $titulo, $ayuda, $columnas, $columnasPc])
                @php $vacia = ['diente' => ''] + collect($columnas)->mapWithKeys(fn ($c) => [$c[0] => ''])->all(); @endphp
                <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="ex-{{ $campo }}" data-seccion="{{ $campo }}"
                         x-data="{ filas: @js($filas($campo, $vacia)), vacia: @js($vacia) }">
                    <h2 id="ex-{{ $campo }}" class="text-headline font-semibold text-ink">{{ $titulo }}</h2>
                    <p class="mt-0.5 text-sm text-muted">{{ $ayuda }}</p>
                    <div class="mt-4 space-y-3">
                        <template x-for="(fila, i) in filas" :key="i">
                            {{-- Celular: diente + primer dato + quitar en un renglón; el segundo dato abajo a todo lo ancho. --}}
                            <div class="grid grid-cols-[5.5rem_minmax(0,1fr)_2.75rem] items-start gap-2 rounded-2xl border border-line bg-cream/40 p-3 {{ $columnasPc }}">
                                <x-text-input type="number" class="block w-full" placeholder="Diente" min="11" max="85" x-model="fila.diente" ::name="`{{ $campo }}[${i}][diente]`" aria-label="Número de diente" />
                                @foreach ($columnas as [$col, $etiqueta])
                                    <x-text-input class="block w-full {{ $loop->first ? '' : 'col-span-3 row-start-2 sm:col-span-1 sm:row-start-auto' }}" maxlength="300" placeholder="{{ $etiqueta }}" x-model="fila.{{ $col }}" ::name="`{{ $campo }}[${i}][{{ $col }}]`" aria-label="{{ $etiqueta }}" />
                                @endforeach
                                <button type="button" @click="filas.length > 1 ? filas.splice(i, 1) : (filas[0] = { ...vacia })" class="col-start-3 row-start-1 grid h-11 w-11 place-items-center rounded-full text-muted transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 sm:col-start-auto sm:row-start-auto" title="Quitar renglón" aria-label="Quitar renglón">
                                    <x-icon name="trash" />
                                </button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="filas.push({ ...vacia })" class="link mt-3 inline-flex min-h-11 items-center text-sm">+ Agregar diente</button>
                    <x-input-error :messages="$errors->get($campo.'.*')" class="mt-2" />
                </section>
            @endforeach

            {{-- 7. Odontograma --}}
            @include('admin.exploracion-clinica._odontograma', ['odontograma' => old('odontograma', $exploracion->odontograma ?? [])])

            {{-- 8. Otros hallazgos y diagnósticos --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="ex-otros">
                <h2 id="ex-otros" class="text-headline font-semibold text-ink">Otros hallazgos</h2>
                <p class="mt-0.5 text-sm text-muted">Prótesis, restauraciones, aparatos de ortodoncia y su estado.</p>
                <x-textarea id="otros_hallazgos" name="otros_hallazgos" rows="2" class="mt-3" aria-labelledby="ex-otros">{{ old('otros_hallazgos', $exploracion->otros_hallazgos) }}</x-textarea>
            </section>

            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="ex-diagnosticos">
                <h2 id="ex-diagnosticos" class="text-headline font-semibold text-ink">Diagnósticos definitivos</h2>
                <x-textarea id="diagnosticos" name="diagnosticos" rows="3" class="mt-3" aria-labelledby="ex-diagnosticos">{{ old('diagnosticos', $exploracion->diagnosticos) }}</x-textarea>
                <x-input-error :messages="$errors->get('diagnosticos')" class="mt-2" />
            </section>

            {{-- Botones en celular (en PC están arriba). --}}
            <div class="flex flex-wrap items-center gap-3 lg:hidden">
                <x-primary-button>{{ $esNueva ? 'Guardar exploración' : 'Guardar cambios' }}</x-primary-button>
                <a href="{{ route('admin.pacientes.historial.index', $paciente) }}" class="inline-flex min-h-11 items-center justify-center rounded-full text-sm font-semibold text-ink-soft hover:text-ink">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>

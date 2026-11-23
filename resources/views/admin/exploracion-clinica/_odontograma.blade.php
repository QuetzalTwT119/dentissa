{{--
    Odontograma (23/11/2026). Se elige un "pincel" (estado) y se tocan los
    dientes: tocar otra vez con el mismo pincel lo borra. Numeración FDI.
    Rojo = por tratar; azul = ya está o ya se hizo. Espera $odontograma
    (['16' => 'cariado', ...]).
--}}
@use('App\Models\ExploracionClinica')
@php
    $estados = collect(ExploracionClinica::ESTADOS_DIENTE)
        ->map(fn ($e) => ['nombre' => $e[0], 'color' => $e[1], 'simbolo' => $e[2]])->all();
    $temporales = collect(ExploracionClinica::ARCADAS['temporal'])->flatten()->all();
    $conTemporales = collect(array_keys($odontograma))->contains(fn ($d) => in_array((int) $d, $temporales, true));
@endphp
<section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="ex-odontograma" data-odontograma
         x-data="{
             estados: @js($estados),
             dientes: @js((object) $odontograma),
             pincel: 'cariado',
             tocar(numero) {
                 const n = String(numero);
                 if (this.pincel === '' || this.dientes[n] === this.pincel) { delete this.dientes[n]; }
                 else { this.dientes[n] = this.pincel; }
                 this.dientes = { ...this.dientes };
             },
             clases(numero) {
                 const e = this.estados[this.dientes[String(numero)]];
                 if (!e) return 'border-line bg-white text-ink-soft hover:border-peach-300';
                 return e.color === 'rojo' ? 'border-rose-300 bg-rose-50 text-rose-700' : 'border-sky-300 bg-sky-50 text-sky-800';
             },
         }">
    <h2 id="ex-odontograma" class="text-headline font-semibold text-ink">Odontograma</h2>
    <p class="mt-0.5 text-sm text-muted">Elige un estado y toca los dientes. Tocar otra vez con el mismo estado lo quita. <span class="font-semibold text-rose-700">Rojo</span>: por tratar · <span class="font-semibold text-sky-800">azul</span>: ya está o ya se hizo.</p>

    {{-- Pinceles --}}
    <div class="mt-4 flex flex-wrap gap-2" role="radiogroup" aria-label="Estado que se aplica al tocar un diente">
        @foreach ($estados as $clave => $e)
            <button type="button" role="radio" :aria-checked="pincel === '{{ $clave }}'" @click="pincel = '{{ $clave }}'" data-pincel="{{ $clave }}"
                    class="inline-flex min-h-11 items-center gap-2 rounded-full border px-3 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 lg:min-h-9"
                    :class="pincel === '{{ $clave }}' ? 'border-ink bg-ink text-white' : '{{ $e['color'] === 'rojo' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-sky-200 bg-sky-50 text-sky-800' }}'">
                <span aria-hidden="true" class="w-3 text-center">{{ $e['simbolo'] }}</span>{{ $e['nombre'] }}
            </button>
        @endforeach
        <button type="button" role="radio" :aria-checked="pincel === ''" @click="pincel = ''"
                class="inline-flex min-h-11 items-center gap-2 rounded-full border px-3 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 lg:min-h-9"
                :class="pincel === '' ? 'border-ink bg-ink text-white' : 'border-line bg-white text-ink-soft'">
            Borrar
        </button>
    </div>

    @foreach ([['permanente', 'Dientes permanentes', true], ['temporal', 'Dientes de leche (temporales)', $conTemporales]] as [$tipo, $titulo, $abierto])
        <details class="group mt-5 rounded-2xl border border-line" @if ($abierto) open @endif data-arcada="{{ $tipo }}">
            <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between px-4 text-sm font-semibold text-ink [&::-webkit-details-marker]:hidden">
                {{ $titulo }}
                <svg class="h-4 w-4 text-muted transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
            </summary>
            <div class="space-y-4 border-t border-line px-3 py-4 sm:px-4">
                @foreach (['superior' => 'Arriba', 'inferior' => 'Abajo'] as $lado => $nombreLado)
                    <div>
                        <p class="mb-2 text-center text-xs font-semibold uppercase tracking-label text-muted">{{ $nombreLado }}</p>
                        {{-- Derecha del paciente a la izquierda de la pantalla (vista de frente). --}}
                        <div class="flex flex-col items-center gap-2 lg:flex-row lg:justify-center lg:gap-0">
                            @foreach (ExploracionClinica::ARCADAS[$tipo][$lado] as $i => $cuadrante)
                                <div @class(['flex gap-1', 'lg:border-r lg:border-dashed lg:border-line lg:pr-3' => $i === 0, 'lg:pl-3' => $i === 1])>
                                    @foreach ($cuadrante as $numero)
                                        <button type="button" @click="tocar({{ $numero }})" data-diente="{{ $numero }}"
                                                :class="clases({{ $numero }})"
                                                :title="'Diente {{ $numero }}: ' + (estados[dientes['{{ $numero }}']]?.nombre ?? 'sin marcar')"
                                                :aria-label="'Diente {{ $numero }}: ' + (estados[dientes['{{ $numero }}']]?.nombre ?? 'sin marcar')"
                                                class="flex h-12 w-8 flex-col items-center justify-center rounded-lg border text-xs font-semibold tabular-nums transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 sm:w-9">
                                            <span>{{ $numero }}</span>
                                            <span class="h-4 text-sm leading-4" x-text="estados[dientes['{{ $numero }}']]?.simbolo ?? ''"></span>
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
    @endforeach

    {{-- Lo marcado viaja oculto. --}}
    <template x-for="(estado, numero) in dientes" :key="numero">
        <input type="hidden" :name="`odontograma[${numero}]`" :value="estado">
    </template>

    <p class="mt-4 text-sm text-ink-soft" data-odontograma-resumen>
        <span class="font-semibold text-ink">Marcados:</span>
        <span x-text="Object.keys(dientes).length === 0 ? 'ninguno' : Object.entries(Object.values(dientes).reduce((t, e) => (t[e] = (t[e] ?? 0) + 1, t), {})).map(([e, n]) => `${estados[e].nombre}: ${n}`).join(' · ')"></span>
    </p>
    <x-input-error :messages="$errors->get('odontograma.*')" class="mt-2" />
</section>

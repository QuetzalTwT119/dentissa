{{--
    Calendario de disponibilidad: el mes a la izquierda (cada día marca si tiene
    lugar) y los horarios del día elegido a la derecha; en celular, uno debajo
    del otro.

    Espera un x-data padre con: idTratamiento, fecha, hora, horarios,
    loadingHorarios y cargarHorarios(). El formulario envía `fecha` y `hora`
    con sus propios <input type="hidden">.

    Opcional: $excluirCitaId (al reagendar, la cita que se mueve no ocupa su horario).
--}}
<div x-data="calendarioDisponibilidad({ hoy: @js(now()->toDateString()), excluirCitaId: @js($excluirCitaId ?? null), mesesAdelante: @js(\App\Http\Controllers\DisponibilidadController::MESES_ADELANTE) })">
    <p x-show="!idTratamiento" class="rounded-2xl bg-cream px-4 py-5 text-center text-sm text-muted">Selecciona un tratamiento para ver los días con lugar.</p>

    <div x-show="idTratamiento" x-cloak class="grid gap-7 md:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] md:gap-8">
        {{-- Mes --}}
        <div>
            <div class="mb-3 flex items-center justify-between gap-3">
                <p class="whitespace-nowrap font-serif text-title-3 text-ink md:text-headline md:font-semibold" x-text="etiquetaMes" aria-live="polite"></p>
                <div class="flex gap-2">
                    @foreach ([-1 => ['Mes anterior', 'M15 19l-7-7 7-7'], 1 => ['Mes siguiente', 'M9 5l7 7-7 7']] as $paso => [$etiqueta, $trazo])
                        <button type="button" @click="cambiarMes({{ $paso }})" :disabled="{{ $paso < 0 ? '!puedeRetroceder' : '!puedeAvanzar' }}" aria-label="{{ $etiqueta }}"
                                class="flex h-11 w-11 items-center justify-center rounded-full border border-line bg-white text-ink-soft transition-[border-color,color,transform] duration-150 ease-out hover:border-peach-300 hover:text-brand-700 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 disabled:opacity-40 disabled:active:scale-100">
                            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $trazo }}" /></svg>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-7 gap-1 text-center" aria-hidden="true">
                <template x-for="(nombre, i) in nombresDias" :key="i">
                    <span class="py-1.5 text-xs font-semibold text-muted" x-text="nombre"></span>
                </template>
            </div>

            <div x-ref="dias" role="group" aria-label="Días del mes" :aria-busy="cargandoMes.toString()" @keydown="moverFoco($event)"
                 class="grid grid-cols-7 gap-1 text-center transition-opacity duration-150"
                 :class="cargandoMes && 'opacity-60'">
                <template x-for="celda in celdas" :key="celda.clave">
                    <button type="button"
                            :data-fecha="celda.hueco ? null : celda.fecha"
                            :disabled="celda.estado !== 'disponible'"
                            :aria-hidden="celda.hueco ? 'true' : null"
                            :aria-pressed="celda.hueco ? null : (fecha === celda.fecha).toString()"
                            :aria-label="celda.hueco ? null : etiquetaDia(celda)"
                            @click="elegirDia(celda.fecha)"
                            class="relative h-11 rounded-xl text-callout tabular-nums transition-[background-color,color,transform] duration-150 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 active:scale-[0.94] disabled:active:scale-100"
                            :class="{
                                'invisible': celda.hueco,
                                'bg-brand-600 font-semibold text-white shadow-brand': !celda.hueco && fecha === celda.fecha,
                                'font-semibold text-ink hover:bg-peach-50': celda.estado === 'disponible' && fecha !== celda.fecha,
                                'text-muted/60': !celda.hueco && celda.estado !== 'disponible',
                                'line-through decoration-muted/50': celda.estado === 'sin_horarios',
                                'ring-1 ring-inset ring-peach-300': celda.fecha === hoy && fecha !== celda.fecha,
                            }">
                        <span x-text="celda.numero"></span>
                        <span x-show="celda.estado === 'disponible'" aria-hidden="true"
                              class="absolute bottom-1.5 left-1/2 h-[5px] w-[5px] -translate-x-1/2 rounded-full"
                              :class="fecha === celda.fecha ? 'bg-white' : (celda.horarios <= 3 ? 'bg-peach-400' : 'bg-sage-500')"></span>
                    </button>
                </template>
            </div>

            <p x-show="errorMes" x-cloak class="mt-3 flex flex-wrap items-center gap-x-2 rounded-xl bg-rose-50 px-3 py-2.5 text-sm text-rose-700" role="alert">
                No pudimos consultar el calendario.
                <button type="button" @click="cargarMes()" class="link">Reintentar</button>
            </p>

            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1.5 text-footnote text-muted">
                <span class="inline-flex items-center gap-1.5"><span class="h-[7px] w-[7px] rounded-full bg-sage-500" aria-hidden="true"></span>Disponible</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-[7px] w-[7px] rounded-full bg-peach-400" aria-hidden="true"></span>Pocos lugares</span>
                <span class="inline-flex items-center gap-1.5"><span class="line-through decoration-muted/50" aria-hidden="true">12</span>Sin horarios</span>
            </div>
        </div>

        {{-- Horarios del día elegido --}}
        <div class="md:border-l md:border-line md:pl-8">
            <template x-if="!fecha">
                <p class="rounded-2xl bg-cream px-4 py-5 text-center text-sm text-muted">Elige un día del calendario para ver sus horarios.</p>
            </template>

            <template x-if="fecha && loadingHorarios">
                <div class="grid gap-2" aria-busy="true"><span class="sr-only">Cargando horarios disponibles…</span><template x-for="i in 5" :key="i"><span class="h-[52px] animate-pulse rounded-2xl bg-shell"></span></template></div>
            </template>

            <template x-if="fecha && !loadingHorarios && horarios.length === 0">
                <p class="rounded-2xl bg-rose-50 px-4 py-5 text-center text-sm font-medium text-rose-700">No hay horarios disponibles ese día. Prueba con otra fecha.</p>
            </template>

            {{-- La clave por fecha vuelve a montar la lista: su entrada marca que cambió el día. --}}
            <template x-for="dia in (fecha && !loadingHorarios && horarios.length > 0 ? [fecha] : [])" :key="dia">
                <div class="horarios-dia">
                    <p class="text-xs font-semibold uppercase tracking-label text-muted" x-text="`${horarios.length} ${horarios.length === 1 ? 'horario disponible' : 'horarios disponibles'}`"></p>
                    <p id="titulo-horarios" class="mt-1 font-serif text-title-3 text-ink" x-text="fechaElegidaLegible"></p>

                    <div class="-m-0.5 mt-3.5 grid max-h-[23.5rem] gap-2 overflow-y-auto p-0.5" role="radiogroup" aria-labelledby="titulo-horarios">
                        <template x-for="horario in horarios" :key="horario">
                            <button type="button" role="radio"
                                    :aria-checked="(hora === horario).toString()"
                                    @click="hora = horario"
                                    class="flex min-h-[52px] w-full items-center justify-between gap-3 rounded-2xl border px-4 text-left transition-[background-color,border-color,transform] duration-150 ease-out active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                    :class="hora === horario ? 'border-brand-400 bg-brand-50/60 ring-1 ring-brand-400' : 'border-line bg-white hover:border-peach-300'">
                                <span class="tabular-nums">
                                    <span class="text-headline font-semibold text-ink" x-text="horario"></span>
                                    <span x-show="duracion" class="ml-1 text-footnote text-muted" x-text="`a ${finHora(horario)}`"></span>
                                </span>
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors duration-150"
                                      :class="hora === horario ? 'border-brand-600 bg-brand-600' : 'border-line bg-white'" aria-hidden="true">
                                    <span class="h-1.5 w-1.5 rounded-full bg-white" x-show="hora === horario"></span>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

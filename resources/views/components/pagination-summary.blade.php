@props(['items', 'label' => 'registros', 'integrated' => true, 'posicion' => 'arriba'])

{{--
    Barra de paginación de las listas.

    Decisión del 20/11/2026 (revisada con el usuario):
    - posicion="arriba" (ANTES de la tabla): a la izquierda "Mostrando 1–12 de 12"
      + "Mostrar 20 por página"; a la derecha las acciones (<x-slot:acciones>,
      p. ej. "Nuevo tratamiento"). Con acciones se muestra aunque la lista esté vacía.
    - posicion="abajo" (DESPUÉS de la tabla): las páginas centradas, o
      "Página 1 de 1" si solo hay una.
    - integrated=false: sin fondo ni bordes de tarjeta (listas en cuadrícula).
--}}
@php
    $conAcciones = isset($acciones) && $acciones->isNotEmpty();
@endphp
@if ($items && ($items->total() > 0 || ($conAcciones && ($posicion ?? 'arriba') === 'arriba')))
    @php
        // "1 cita" / "1,284 citas": singular cuando hay exactamente uno y
        // separador de miles al estilo de México.
        $singulares = ['imágenes' => 'imagen', 'notificaciones' => 'notificación', 'mensajes' => 'mensaje', 'pacientes' => 'paciente'];
        $etiquetaTotal = $items->total() === 1 ? ($singulares[$label] ?? preg_replace('/s$/u', '', $label)) : $label;
        $totalFormateado = number_format($items->total(), 0, '.', ',');
        $paginator = $items->withQueryString();
        $hasPages = $paginator->hasPages();
        $perPageActual = $paginator->perPage();
        $sufijo = $paginator->getPageName().'-'.$posicion.($integrated ? '' : '-suelta');
        $arriba = $posicion === 'arriba';
    @endphp

    @if (! $arriba)
        <div {{ $attributes->merge(['class' => $integrated ? 'border-t border-line/80 bg-cream/50 px-4 py-3 sm:px-6' : 'mt-4 pt-3']) }} data-paginacion="abajo">
            <div class="flex justify-center">
                @if ($hasPages)
                    {{ $paginator->onEachSide(1)->links('vendor.pagination.dentissa') }}
                @else
                    <span class="text-caption font-medium text-muted/75">Página 1 de 1</span>
                @endif
            </div>
        </div>
    @else
        <div {{ $attributes->merge(['class' => $integrated ? 'border-b border-line/80 bg-cream/50 px-4 py-3 sm:px-6' : 'mb-4 border-b border-line/80 pb-3 text-xs text-muted']) }} data-paginacion="arriba">
            {{-- En celular, la acción principal va primero; en pantallas anchas, a la derecha. --}}
            <div class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                @if ($items->total() > 0)
                <div class="flex flex-wrap items-center justify-center gap-3 sm:justify-start sm:gap-4">
                    <p class="inline-flex items-center gap-2 text-xs text-muted">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500" aria-hidden="true"></span>
                        <span>
                            Mostrando <strong class="font-semibold text-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
                            de <strong class="font-semibold tabular-nums text-ink">{{ $totalFormateado }}</strong> {{ $etiquetaTotal }}
                        </span>
                    </p>

                    {{-- Selector de registros por página con diseño Dentissa. --}}
                    <div x-data="{ open: false, perPage: {{ $perPageActual }} }"
                         @click.outside="open = false"
                         @keydown.escape.window="open = false"
                         class="relative inline-flex items-center gap-1.5 text-xs text-muted">

                        <form method="GET" action="{{ request()->url() }}" x-ref="formPerPage" class="inline-flex items-center gap-1.5">
                            @foreach (request()->except(['page', 'per_page']) as $name => $value)
                                @if (! is_array($value))
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            {{-- Select nativo oculto: es el que se envía (compatibilidad y pruebas). --}}
                            <select id="per-page-{{ $sufijo }}" name="per_page" x-ref="nativeSelect" class="hidden" aria-hidden="true" tabindex="-1">
                                <option value="20" @selected($perPageActual === 20)>20</option>
                                <option value="50" @selected($perPageActual === 50)>50</option>
                            </select>

                            <label for="per-page-custom-{{ $sufijo }}" class="font-medium text-ink-soft">Mostrar</label>

                            <div class="relative">
                                <button id="per-page-custom-{{ $sufijo }}"
                                        type="button"
                                        @click="open = !open"
                                        :aria-expanded="open.toString()"
                                        aria-haspopup="listbox"
                                        aria-label="Cantidad de registros por página: actualmente {{ $perPageActual }}"
                                        class="group inline-flex min-h-11 items-center justify-between gap-1.5 rounded-xl border border-line bg-white px-3 py-1 text-xs shadow-2xs transition hover:border-peach-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 sm:min-h-9">
                                    <span x-text="perPage" class="tabular-nums font-semibold">{{ $perPageActual }}</span>
                                    <svg class="h-3.5 w-3.5 text-muted transition-transform duration-200 group-hover:text-brand-500"
                                         :class="{ 'rotate-180 text-brand-500': open }"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                {{-- Arriba de la tabla el menú abre hacia abajo. --}}
                                <div x-cloak
                                     x-show="open"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                     x-transition:leave="transition ease-out duration-100"
                                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                     x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                     role="listbox"
                                     aria-label="Opciones de registros por página"
                                     class="absolute right-0 top-full z-50 mt-1.5 min-w-[5.75rem] overflow-hidden rounded-2xl border border-line bg-white p-1 shadow-lift">
                                    @foreach ([20, 50] as $opcion)
                                        <button type="button"
                                                role="option"
                                                :aria-selected="perPage === {{ $opcion }}"
                                                @click="if (perPage !== {{ $opcion }}) { perPage = {{ $opcion }}; $refs.nativeSelect.value = {{ $opcion }}; open = false; $nextTick(() => $refs.formPerPage.submit()); } else { open = false; }"
                                                class="flex w-full items-center justify-between gap-2.5 rounded-xl px-2.5 py-1.5 text-xs font-medium transition-colors"
                                                :class="perPage === {{ $opcion }} ? 'bg-brand-50 text-brand-500 font-semibold' : 'text-ink hover:bg-cream hover:text-ink'">
                                            <span class="tabular-nums">{{ $opcion }}</span>
                                            <svg x-show="perPage === {{ $opcion }}" class="h-3.5 w-3.5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <span class="font-medium text-ink-soft">por página</span>
                        </form>
                    </div>
                </div>
                @else
                    <span class="hidden sm:block" aria-hidden="true"></span>
                @endif

                {{-- Derecha: acciones de la lista. --}}
                @if ($conAcciones)
                    <div class="flex justify-center sm:justify-end">{{ $acciones }}</div>
                @endif
            </div>
        </div>
    @endif
@endif

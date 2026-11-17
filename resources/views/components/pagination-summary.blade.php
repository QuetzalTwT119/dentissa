@props(['items', 'label' => 'registros', 'integrated' => true])

@if ($items && $items->total() > 0)
    @php
        $paginator = $items->withQueryString();
        $hasPages = $paginator->hasPages();
        $perPageActual = $paginator->perPage();
    @endphp

    @if ($integrated)
        <div {{ $attributes->merge(['class' => 'border-t border-line/80 bg-cream/50 px-4 py-3 sm:px-6']) }}>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-start sm:gap-4">
                    <p class="inline-flex items-center gap-2 text-xs text-muted">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500" aria-hidden="true"></span>
                        <span>
                            Mostrando <strong class="font-semibold text-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
                            de <strong class="font-semibold text-ink">{{ $paginator->total() }}</strong> {{ $label }}
                        </span>
                    </p>

                    <!-- Selector estilizado de registros por página con diseño Dentissa -->
                    <div x-data="{ open: false, perPage: {{ $perPageActual }} }"
                         @click.outside="open = false"
                         @keydown.escape.window="open = false"
                         class="relative inline-flex items-center gap-1.5 text-xs text-muted">
                        
                        <form method="GET" action="{{ request()->url() }}" x-ref="formPerPage" class="inline-flex items-center gap-1.5">
                            @foreach (request()->except(['page', 'per_page']) as $name => $value)
                                @if (!is_array($value))
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <!-- Select semántico nativo para compatibilidad y pruebas -->
                            <select id="per-page-{{ $paginator->getPageName() }}"
                                    name="per_page"
                                    x-ref="nativeSelect"
                                    class="hidden"
                                    aria-hidden="true"
                                    tabindex="-1">
                                <option value="20" @selected($perPageActual === 20)>20</option>
                                <option value="50" @selected($perPageActual === 50)>50</option>
                            </select>

                            <label for="per-page-custom-{{ $paginator->getPageName() }}" class="font-medium text-ink-soft">Mostrar</label>

                            <div class="relative">
                                <button id="per-page-custom-{{ $paginator->getPageName() }}"
                                        type="button"
                                        @click="open = !open"
                                        :aria-expanded="open.toString()"
                                        aria-haspopup="listbox"
                                        aria-label="Cantidad de registros por página: actualmente {{ $perPageActual }}"
                                        class="group inline-flex items-center justify-between gap-1.5 rounded-xl border border-line bg-white px-2.5 py-1 text-xs font-semibold text-ink shadow-2xs transition-all duration-150 hover:border-brand-300 hover:bg-cream hover:text-brand-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/25">
                                    <span x-text="perPage" class="tabular-nums font-semibold">{{ $perPageActual }}</span>
                                    <svg class="h-3.5 w-3.5 text-muted transition-transform duration-200 group-hover:text-brand-500"
                                         :class="{ 'rotate-180 text-brand-500': open }"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div x-cloak
                                     x-show="open"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                     x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                                     role="listbox"
                                     aria-label="Opciones de registros por página"
                                     class="absolute bottom-full mb-1.5 left-0 z-50 min-w-[5.75rem] overflow-hidden rounded-2xl border border-line bg-white p-1 shadow-[0_10px_25px_-5px_rgba(45,37,38,0.12),0_8px_10px_-6px_rgba(45,37,38,0.06)] ring-1 ring-black/5">
                                    
                                    <button type="button"
                                            role="option"
                                            :aria-selected="perPage === 20"
                                            @click="if (perPage !== 20) { perPage = 20; $refs.nativeSelect.value = 20; open = false; $nextTick(() => $refs.formPerPage.submit()); } else { open = false; }"
                                            class="flex w-full items-center justify-between gap-2.5 rounded-xl px-2.5 py-1.5 text-xs font-medium transition-colors"
                                            :class="perPage === 20 ? 'bg-brand-50 text-brand-500 font-semibold' : 'text-ink hover:bg-cream hover:text-ink'">
                                        <span class="tabular-nums">20</span>
                                        <svg x-show="perPage === 20" class="h-3.5 w-3.5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    <button type="button"
                                            role="option"
                                            :aria-selected="perPage === 50"
                                            @click="if (perPage !== 50) { perPage = 50; $refs.nativeSelect.value = 50; open = false; $nextTick(() => $refs.formPerPage.submit()); } else { open = false; }"
                                            class="flex w-full items-center justify-between gap-2.5 rounded-xl px-2.5 py-1.5 text-xs font-medium transition-colors"
                                            :class="perPage === 50 ? 'bg-brand-50 text-brand-500 font-semibold' : 'text-ink hover:bg-cream hover:text-ink'">
                                        <span class="tabular-nums">50</span>
                                        <svg x-show="perPage === 50" class="h-3.5 w-3.5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <span class="font-medium text-ink-soft">por página</span>
                        </form>
                    </div>
                </div>

                @if ($hasPages)
                    <div class="flex items-center justify-center sm:justify-end">
                        {{ $paginator->onEachSide(1)->links('vendor.pagination.dentissa') }}
                    </div>
                @else
                    <span class="hidden sm:inline-block text-caption font-medium text-muted/75">
                        Página 1 de 1
                    </span>
                @endif
            </div>
        </div>
    @else
        <div {{ $attributes->merge(['class' => 'mt-4 border-t border-line/80 pt-3 text-xs text-muted']) }}>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center justify-between gap-3 sm:justify-start sm:gap-4">
                    <p class="inline-flex items-center gap-2">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500" aria-hidden="true"></span>
                        <span>
                            Mostrando <strong class="font-semibold text-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
                            de <strong class="font-semibold text-ink">{{ $paginator->total() }}</strong> {{ $label }}
                        </span>
                    </p>

                    <!-- Selector estilizado de registros por página con diseño Dentissa -->
                    <div x-data="{ open: false, perPage: {{ $perPageActual }} }"
                         @click.outside="open = false"
                         @keydown.escape.window="open = false"
                         class="relative inline-flex items-center gap-1.5 text-xs text-muted">
                        
                        <form method="GET" action="{{ request()->url() }}" x-ref="formPerPageStandalone" class="inline-flex items-center gap-1.5">
                            @foreach (request()->except(['page', 'per_page']) as $name => $value)
                                @if (!is_array($value))
                                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                                @endif
                            @endforeach

                            <select id="per-page-standalone-{{ $paginator->getPageName() }}"
                                    name="per_page"
                                    x-ref="nativeSelectStandalone"
                                    class="hidden"
                                    aria-hidden="true"
                                    tabindex="-1">
                                <option value="20" @selected($perPageActual === 20)>20</option>
                                <option value="50" @selected($perPageActual === 50)>50</option>
                            </select>

                            <label for="per-page-custom-standalone-{{ $paginator->getPageName() }}" class="font-medium text-ink-soft">Mostrar</label>

                            <div class="relative">
                                <button id="per-page-custom-standalone-{{ $paginator->getPageName() }}"
                                        type="button"
                                        @click="open = !open"
                                        :aria-expanded="open.toString()"
                                        aria-haspopup="listbox"
                                        aria-label="Cantidad de registros por página: actualmente {{ $perPageActual }}"
                                        class="group inline-flex items-center justify-between gap-1.5 rounded-xl border border-line bg-white px-2.5 py-1 text-xs font-semibold text-ink shadow-2xs transition-all duration-150 hover:border-brand-300 hover:bg-cream hover:text-brand-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/25">
                                    <span x-text="perPage" class="tabular-nums font-semibold">{{ $perPageActual }}</span>
                                    <svg class="h-3.5 w-3.5 text-muted transition-transform duration-200 group-hover:text-brand-500"
                                         :class="{ 'rotate-180 text-brand-500': open }"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div x-cloak
                                     x-show="open"
                                     x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                     x-transition:leave="transition ease-in duration-100"
                                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                     x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                                     role="listbox"
                                     aria-label="Opciones de registros por página"
                                     class="absolute bottom-full mb-1.5 left-0 z-50 min-w-[5.75rem] overflow-hidden rounded-2xl border border-line bg-white p-1 shadow-[0_10px_25px_-5px_rgba(45,37,38,0.12),0_8px_10px_-6px_rgba(45,37,38,0.06)] ring-1 ring-black/5">
                                    
                                    <button type="button"
                                            role="option"
                                            :aria-selected="perPage === 20"
                                            @click="if (perPage !== 20) { perPage = 20; $refs.nativeSelectStandalone.value = 20; open = false; $nextTick(() => $refs.formPerPageStandalone.submit()); } else { open = false; }"
                                            class="flex w-full items-center justify-between gap-2.5 rounded-xl px-2.5 py-1.5 text-xs font-medium transition-colors"
                                            :class="perPage === 20 ? 'bg-brand-50 text-brand-500 font-semibold' : 'text-ink hover:bg-cream hover:text-ink'">
                                        <span class="tabular-nums">20</span>
                                        <svg x-show="perPage === 20" class="h-3.5 w-3.5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    <button type="button"
                                            role="option"
                                            :aria-selected="perPage === 50"
                                            @click="if (perPage !== 50) { perPage = 50; $refs.nativeSelectStandalone.value = 50; open = false; $nextTick(() => $refs.formPerPageStandalone.submit()); } else { open = false; }"
                                            class="flex w-full items-center justify-between gap-2.5 rounded-xl px-2.5 py-1.5 text-xs font-medium transition-colors"
                                            :class="perPage === 50 ? 'bg-brand-50 text-brand-500 font-semibold' : 'text-ink hover:bg-cream hover:text-ink'">
                                        <span class="tabular-nums">50</span>
                                        <svg x-show="perPage === 50" class="h-3.5 w-3.5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <span class="font-medium text-ink-soft">por página</span>
                        </form>
                    </div>
                </div>

                @if ($hasPages)
                    <div class="flex items-center justify-center sm:justify-end">
                        {{ $paginator->onEachSide(1)->links('vendor.pagination.dentissa') }}
                    </div>
                @else
                    <span class="hidden sm:inline-block text-caption font-medium text-muted/75">
                        Página 1 de 1
                    </span>
                @endif
            </div>
        </div>
    @endif
@endif

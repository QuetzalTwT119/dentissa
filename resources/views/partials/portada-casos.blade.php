{{--
    Casos antes / después (24/11/2026). Se capturan en Contenido → Casos: la
    primera foto subida es "Antes" y la segunda "Después". Antes la portada los
    recibía pero no los mostraba. Solo aparecen los casos activos con foto.
--}}
@php
    $casosConFoto = ($casos ?? collect())
        ->map(fn ($caso) => [$caso, $caso->imagenes->sortBy([['orden', 'asc'], ['id', 'asc']])->values()])
        ->filter(fn ($par) => $par[1]->isNotEmpty())
        ->take(3);
@endphp
@if ($casosConFoto->isNotEmpty())
    <section id="casos" aria-labelledby="titulo-casos" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28" data-casos>
        <div class="max-w-2xl">
            <p class="eyebrow">Resultados reales</p>
            <h2 id="titulo-casos" class="mt-4 text-title-1 text-ink">Casos antes y después</h2>
            <p class="mt-3 text-callout text-muted">Tratamientos de pacientes del consultorio, publicados con su autorización.</p>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($casosConFoto as [$caso, $fotos])
                <article class="flex flex-col overflow-hidden rounded-3xl border border-line bg-white shadow-soft" data-caso>
                    <div @class(['grid gap-px bg-line', 'grid-cols-2' => $fotos->count() > 1])>
                        @foreach ($fotos->take(2) as $foto)
                            <figure class="relative aspect-[4/5] bg-shell">
                                <img src="{{ asset('storage/'.$foto->url) }}" alt="{{ $caso->titulo }}{{ $fotos->count() > 1 ? ($loop->first ? ' — antes' : ' — después') : '' }}"
                                     class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                                @if ($fotos->count() > 1)
                                    <figcaption class="absolute left-2 top-2 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-ink">{{ $loop->first ? 'Antes' : 'Después' }}</figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                    <div class="flex flex-1 flex-col gap-2 p-5">
                        <h3 class="font-serif text-title-3 font-medium text-ink">{{ $caso->titulo }}</h3>
                        @if (filled($caso->descripcion))
                            <p class="text-callout text-muted">{{ $caso->descripcion }}</p>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

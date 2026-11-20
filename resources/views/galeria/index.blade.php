<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Instalaciones y Resultados"
            title="Galería"
            subtitle="Fotografías de las instalaciones, equipamiento y resultados del consultorio."
        />
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-pagination-summary :items="$imagenes" label="imágenes" :integrated="false" />

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 sm:gap-6 items-stretch">
                @forelse ($imagenes as $imagen)
                    <div class="overflow-hidden rounded-2xl border border-line bg-white shadow-soft transition hover:border-peach-300 h-full flex flex-col justify-between group">
                        <div>
                            <div class="relative h-48 w-full overflow-hidden bg-stone-100">
                                <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                            </div>
                            @if ($imagen->titulo || $imagen->descripcion)
                                <div class="p-4 sm:p-5">
                                    @if ($imagen->titulo)
                                        <p class="text-sm font-semibold text-ink">{{ $imagen->titulo }}</p>
                                    @endif
                                    @if ($imagen->descripcion)
                                        <p class="mt-1.5 text-xs text-ink-soft leading-relaxed">{{ $imagen->descripcion }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-line bg-white p-12 text-center text-sm text-muted shadow-soft">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 border border-brand-100">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h4 class="font-semibold text-ink">Todavía no hay imágenes en la galería</h4>
                        <p class="mt-1 text-xs">Las fotografías clínicas e instalaciones se mostrarán aquí una vez registradas.</p>
                    </div>
                @endforelse
            </div>
            <x-pagination-summary :items="$imagenes" label="imágenes" :integrated="false" posicion="abajo" />
        </div>
    </div>
</x-app-layout>

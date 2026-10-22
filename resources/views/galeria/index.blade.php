<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
            Galería
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto grid max-w-6xl grid-cols-2 gap-4 sm:grid-cols-3 sm:px-6 lg:grid-cols-4 lg:px-8">
            @forelse ($imagenes as $imagen)
                <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft transition hover:shadow-md hover:border-[#f2b0a6]">
                    <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-44 w-full object-cover">
                    @if ($imagen->titulo || $imagen->descripcion)
                        <div class="p-4">
                            @if ($imagen->titulo)
                                <p class="text-sm font-semibold text-[#2D2526]">{{ $imagen->titulo }}</p>
                            @endif
                            @if ($imagen->descripcion)
                                <p class="mt-1 text-xs text-[#8A7A7D] leading-relaxed">{{ $imagen->descripcion }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-[#e9dfdb] bg-white p-12 text-center text-sm text-[#8A7A7D] shadow-soft">
                    <p class="font-medium">Todavía no hay imágenes en la galería.</p>
                    <p class="mt-1 text-xs text-[#8A7A7D]">Las fotografías clínicas e instalaciones se mostrarán aquí una vez registradas.</p>
                </div>
            @endforelse
        </div>
        <x-pagination-summary :items="$imagenes" label="imágenes" :integrated="false" />
    </div>
</x-app-layout>

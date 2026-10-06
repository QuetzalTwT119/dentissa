<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Galería
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto grid max-w-6xl grid-cols-2 gap-4 sm:grid-cols-3 sm:px-6 lg:grid-cols-4 lg:px-8">
            @forelse ($imagenes as $imagen)
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-40 w-full object-cover">
                    @if ($imagen->titulo || $imagen->descripcion)
                        <div class="p-3">
                            @if ($imagen->titulo)
                                <p class="text-sm font-medium text-gray-900">{{ $imagen->titulo }}</p>
                            @endif
                            @if ($imagen->descripcion)
                                <p class="mt-1 text-xs text-gray-500">{{ $imagen->descripcion }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full bg-white p-6 text-center text-sm text-gray-500 shadow-sm sm:rounded-lg">
                    Todavía no hay imágenes en la galería.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Galería
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Fotografías de las instalaciones, equipo e infraestructura de la clínica.</p>
            </div>
            <a href="{{ route('admin.galeria.create') }}" class="self-start sm:self-auto">
                <x-primary-button>Nueva imagen</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-[#6b5e62]">
                    Fotos del consultorio visibles para pacientes y personal.
                </p>

                <a href="{{ route('admin.galeria.create') }}">
                    <x-primary-button>Nueva imagen</x-primary-button>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                @forelse ($imagenes as $imagen)
                    <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft transition hover:border-[#f2b0a6] hover:shadow-md">
                        <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-44 w-full object-cover">
                        <div class="p-4">
                            <h3 class="truncate text-sm font-semibold text-[#2D2526]">{{ $imagen->titulo ?: 'Sin título' }}</h3>
                            <p class="mt-1 text-xs text-[#8A7A7D]">
                                Orden: {{ $imagen->orden }} ·
                                <span class="font-medium {{ $imagen->activa ? 'text-[#4E8D72]' : 'text-gray-400' }}">
                                    {{ $imagen->activa ? 'Activa' : 'Inactiva' }}
                                </span>
                            </p>
                            <div class="mt-4 flex items-center justify-between border-t border-[#e9dfdb]/60 pt-3 text-xs font-semibold">
                                <a href="{{ route('admin.galeria.edit', $imagen) }}" class="text-brand-600 hover:text-brand-800 transition">Editar</a>
                                <form method="POST" action="{{ route('admin.galeria.destroy', $imagen) }}"
                                      onsubmit="return confirm('¿Eliminar esta imagen de la galería? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 transition">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-[#e9dfdb] bg-white p-12 text-center text-sm text-[#8A7A7D] shadow-soft">
                        No hay imágenes en la galería todavía.
                    </div>
                @endforelse
            </div>
            <x-pagination-summary :items="$imagenes" label="imágenes" :integrated="false" />
        </div>
    </div>
</x-app-layout>

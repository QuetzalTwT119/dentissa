<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Galería
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Fotos del consultorio visibles para pacientes y personal.
                </p>

                <a href="{{ route('admin.galeria.create') }}">
                    <x-primary-button>Nueva imagen</x-primary-button>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @forelse ($imagenes as $imagen)
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-32 w-full object-cover">
                        <div class="p-3">
                            <p class="truncate text-sm font-medium text-gray-900">{{ $imagen->titulo ?: 'Sin título' }}</p>
                            <p class="text-xs text-gray-500">
                                Orden: {{ $imagen->orden }} ·
                                <span class="{{ $imagen->activa ? 'text-green-700' : 'text-gray-400' }}">
                                    {{ $imagen->activa ? 'Activa' : 'Inactiva' }}
                                </span>
                            </p>
                            <div class="mt-2 space-x-3 text-sm">
                                <a href="{{ route('admin.galeria.edit', $imagen) }}" class="text-brand-600 hover:text-brand-900">Editar</a>
                                <form method="POST" action="{{ route('admin.galeria.destroy', $imagen) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar esta imagen de la galería? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white p-6 text-center text-sm text-gray-500 shadow-sm sm:rounded-lg">
                        No hay imágenes en la galería todavía.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Casos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between p-6">
                    <p class="text-sm text-gray-600">
                        Casos reales (antes/después) que se muestran en la página principal como resultados del consultorio.
                    </p>

                    <a href="{{ route('admin.casos.create') }}">
                        <x-primary-button>Nuevo caso</x-primary-button>
                    </a>
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Título</th>
                            <th class="px-6 py-3">Descripción</th>
                            <th class="px-6 py-3">Fotos</th>
                            <th class="px-6 py-3">Orden</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($casos as $caso)
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $caso->titulo }}</td>
                                <td class="max-w-sm truncate px-6 py-4 text-gray-600">{{ $caso->descripcion ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $caso->imagenes_count }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $caso->orden }}</td>
                                <td class="px-6 py-4">
                                    <span class="{{ $caso->activo ? 'text-green-700' : 'text-gray-400' }}">
                                        {{ $caso->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('admin.casos.edit', $caso) }}" title="Editar" aria-label="Editar caso"
                                           class="rounded p-1.5 text-brand-600 transition hover:bg-brand-50 hover:text-brand-900">
                                            <x-icon name="edit" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.casos.destroy', $caso) }}"
                                              onsubmit="return confirm('¿Eliminar el caso &quot;{{ $caso->titulo }}&quot;? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" title="Eliminar" aria-label="Eliminar caso"
                                                    class="rounded p-1.5 text-red-600 transition hover:bg-red-50 hover:text-red-900">
                                                <x-icon name="trash" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No hay casos registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

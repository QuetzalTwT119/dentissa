<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Tratamientos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between p-6">
                    <p class="text-sm text-gray-600">
                        Gestiona los procedimientos específicos que ofrece la clínica.
                    </p>

                    <a href="{{ route('admin.tratamientos.create') }}">
                        <x-primary-button>Nuevo tratamiento</x-primary-button>
                    </a>
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Categoría</th>
                            <th class="px-6 py-3">Duración</th>
                            <th class="px-6 py-3">Precio</th>
                            <th class="px-6 py-3">Servicios</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tratamientos as $tratamiento)
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $tratamiento->nombre }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $tratamiento->categoria ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $tratamiento->duracion_estimada ? $tratamiento->duracion_estimada.' min' : '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $tratamiento->precio_referencia ? '$'.$tratamiento->precio_referencia : '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $tratamiento->servicios_count }}</td>
                                <td class="px-6 py-4">
                                    <span class="{{ $tratamiento->activo ? 'text-green-700' : 'text-gray-400' }}">
                                        {{ $tratamiento->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('admin.tratamientos.edit', $tratamiento) }}" title="Editar" aria-label="Editar tratamiento"
                                           class="rounded p-1.5 text-brand-600 transition hover:bg-brand-50 hover:text-brand-900">
                                            <x-icon name="edit" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.tratamientos.destroy', $tratamiento) }}"
                                              onsubmit="return confirm('¿Eliminar el tratamiento &quot;{{ $tratamiento->nombre }}&quot;? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" title="Eliminar" aria-label="Eliminar tratamiento"
                                                    class="rounded p-1.5 text-red-600 transition hover:bg-red-50 hover:text-red-900">
                                                <x-icon name="trash" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    No hay tratamientos registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

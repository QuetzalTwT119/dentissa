<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Preguntas frecuentes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between p-6">
                    <p class="text-sm text-gray-600">
                        Gestiona las preguntas frecuentes que ven los pacientes en el sitio público.
                    </p>

                    <a href="{{ route('admin.preguntas-frecuentes.create') }}">
                        <x-primary-button>Nueva pregunta</x-primary-button>
                    </a>
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Orden</th>
                            <th class="px-6 py-3">Pregunta</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preguntas as $pregunta)
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 text-gray-600">{{ $pregunta->orden }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $pregunta->pregunta }}</td>
                                <td class="px-6 py-4">
                                    <span class="{{ $pregunta->activa ? 'text-green-700' : 'text-gray-400' }}">
                                        {{ $pregunta->activa ? 'Visible' : 'Oculta' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('admin.preguntas-frecuentes.edit', $pregunta) }}" title="Editar" aria-label="Editar pregunta"
                                           class="rounded p-1.5 text-brand-600 transition hover:bg-brand-50 hover:text-brand-900">
                                            <x-icon name="edit" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.preguntas-frecuentes.destroy', $pregunta) }}"
                                              onsubmit="return confirm('¿Eliminar la pregunta &quot;{{ $pregunta->pregunta }}&quot;? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" title="Eliminar" aria-label="Eliminar pregunta"
                                                    class="rounded p-1.5 text-red-600 transition hover:bg-red-50 hover:text-red-900">
                                                <x-icon name="trash" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    No hay preguntas frecuentes registradas todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

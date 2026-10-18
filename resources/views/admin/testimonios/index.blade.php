@php
$estadoLabel = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];
$estadoClase = [
    'pendiente' => 'bg-yellow-100 text-yellow-800',
    'aprobado' => 'bg-green-100 text-green-800',
    'rechazado' => 'bg-red-100 text-red-800',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Testimonios
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex flex-col items-stretch gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <form method="GET" action="{{ route('admin.testimonios.index') }}" class="flex flex-wrap items-center gap-3">
                        <span class="text-sm text-gray-600">Filtrar por estado:</span>
                        <select name="estado" onchange="this.form.submit()"
                                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Todos</option>
                            <option value="pendiente" @selected(($filtros['estado'] ?? '') === 'pendiente')>Pendiente</option>
                            <option value="aprobado" @selected(($filtros['estado'] ?? '') === 'aprobado')>Aprobado</option>
                            <option value="rechazado" @selected(($filtros['estado'] ?? '') === 'rechazado')>Rechazado</option>
                        </select>
                    </form>

                    <a href="{{ route('admin.testimonios.create') }}" class="self-start sm:self-auto">
                        <x-primary-button>Nuevo testimonio</x-primary-button>
                    </a>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Paciente</th>
                            <th class="px-6 py-3">Calificación</th>
                            <th class="px-6 py-3">Comentario</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($testimonios as $testimonio)
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $testimonio->paciente->nombre }} {{ $testimonio->paciente->apellido }}</td>
                                <td class="px-6 py-4 text-amber-500">{{ str_repeat('★', $testimonio->calificacion) }}</td>
                                <td class="max-w-sm truncate px-6 py-4 text-gray-600">{{ $testimonio->comentario }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium {{ $estadoClase[$testimonio->estado->value] }}">
                                        {{ $estadoLabel[$testimonio->estado->value] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('admin.testimonios.edit', $testimonio) }}" title="Editar" aria-label="Editar testimonio"
                                           class="rounded p-1.5 text-brand-600 transition hover:bg-brand-50 hover:text-brand-900">
                                            <x-icon name="edit" />
                                        </a>
                                        @if ($testimonio->estado->value !== 'aprobado')
                                            <form method="POST" action="{{ route('admin.testimonios.aprobar', $testimonio) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" title="Aprobar" aria-label="Aprobar testimonio"
                                                        class="rounded p-1.5 text-green-600 transition hover:bg-green-50 hover:text-green-900">
                                                    <x-icon name="check" />
                                                </button>
                                            </form>
                                        @endif
                                        @if ($testimonio->estado->value !== 'rechazado')
                                            <form method="POST" action="{{ route('admin.testimonios.rechazar', $testimonio) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" title="Rechazar" aria-label="Rechazar testimonio"
                                                        class="rounded p-1.5 text-yellow-600 transition hover:bg-yellow-50 hover:text-yellow-900">
                                                    <x-icon name="x" />
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.testimonios.destroy', $testimonio) }}"
                                              onsubmit="return confirm('¿Eliminar este testimonio? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" title="Eliminar" aria-label="Eliminar testimonio"
                                                    class="rounded p-1.5 text-red-600 transition hover:bg-red-50 hover:text-red-900">
                                                <x-icon name="trash" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No hay testimonios registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

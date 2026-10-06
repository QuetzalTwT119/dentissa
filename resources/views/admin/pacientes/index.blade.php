<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Pacientes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap items-center justify-between gap-4 p-6">
                    <p class="text-sm text-gray-600">
                        Selecciona un paciente para consultar o registrar su historial clínico.
                    </p>

                    <form method="GET" action="{{ route('admin.pacientes.index') }}" class="flex items-center gap-2">
                        <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Buscar por nombre o correo…"
                               class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <x-secondary-button type="submit">Buscar</x-secondary-button>
                        @if (!empty($filtros['buscar']))
                            <a href="{{ route('admin.pacientes.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Limpiar</a>
                        @endif
                    </form>
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Correo</th>
                            <th class="px-6 py-3">Teléfono</th>
                            <th class="px-6 py-3">Consultas</th>
                            <th class="px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pacientes as $paciente)
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $paciente->nombre }} {{ $paciente->apellido }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $paciente->email }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $paciente->telefono }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $paciente->historial_clinico_count }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('admin.pacientes.historial.index', $paciente) }}" title="Ver historial" aria-label="Ver historial clínico"
                                           class="rounded p-1.5 text-brand-600 transition hover:bg-brand-50 hover:text-brand-900">
                                            <x-icon name="eye" />
                                        </a>
                                        <a href="{{ route('admin.pacientes.edit', $paciente) }}" title="Editar datos" aria-label="Editar datos del paciente"
                                           class="rounded p-1.5 text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
                                            <x-icon name="edit" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    @if (!empty($filtros['buscar']))
                                        No hay pacientes que coincidan con "{{ $filtros['buscar'] }}".
                                    @else
                                        No hay pacientes registrados todavía.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

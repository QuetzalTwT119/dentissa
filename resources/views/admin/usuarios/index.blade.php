@php
$rolLabel = ['paciente' => 'Paciente', 'asistente' => 'Asistente', 'admin' => 'Admin'];
$rolClase = [
    'paciente' => 'bg-gray-100 text-gray-700',
    'asistente' => 'bg-blue-100 text-blue-800',
    'admin' => 'bg-pink-100 text-pink-800',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Usuarios
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap items-center justify-between gap-4 p-6">
                    <p class="text-sm text-gray-600">
                        Gestiona las cuentas y los roles del personal que accede a la plataforma.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex items-center gap-3">
                            <select name="rol" aria-label="Filtrar por rol" onchange="this.form.submit()"
                                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Todos los roles</option>
                                <option value="asistente" @selected(($filtros['rol'] ?? '') === 'asistente')>Asistente</option>
                                <option value="admin" @selected(($filtros['rol'] ?? '') === 'admin')>Admin</option>
                            </select>
                            <select name="activo" aria-label="Filtrar por estado" onchange="this.form.submit()"
                                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Todos los estados</option>
                                <option value="1" @selected(($filtros['activo'] ?? '') === '1')>Activos</option>
                                <option value="0" @selected(($filtros['activo'] ?? '') === '0')>Inactivos</option>
                            </select>
                        </form>

                        <a href="{{ route('admin.usuarios.create') }}">
                            <x-primary-button>Nuevo usuario</x-primary-button>
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">Correo</th>
                            <th class="px-6 py-3">Teléfono</th>
                            <th class="px-6 py-3">Rol</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($usuarios as $usuario)
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $usuario->nombre }} {{ $usuario->apellido }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->email }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $usuario->telefono }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium {{ $rolClase[$usuario->rol->value] }}">
                                        {{ $rolLabel[$usuario->rol->value] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="{{ $usuario->activo ? 'text-green-700' : 'text-gray-400' }}">
                                        {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('admin.usuarios.edit', $usuario) }}" title="Editar" aria-label="Editar usuario"
                                           class="rounded p-1.5 text-brand-600 transition hover:bg-brand-50 hover:text-brand-900">
                                            <x-icon name="edit" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.usuarios.activo.toggle', $usuario) }}"
                                              onsubmit="return confirm('¿Seguro que quieres {{ $usuario->activo ? 'desactivar' : 'activar' }} a {{ $usuario->nombre }} {{ $usuario->apellido }}?');">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" title="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}" aria-label="{{ $usuario->activo ? 'Desactivar usuario' : 'Activar usuario' }}"
                                                    class="rounded p-1.5 transition {{ $usuario->activo ? 'text-red-600 hover:bg-red-50 hover:text-red-900' : 'text-green-600 hover:bg-green-50 hover:text-green-900' }}">
                                                <x-icon name="power" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No hay usuarios registrados todavía.
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

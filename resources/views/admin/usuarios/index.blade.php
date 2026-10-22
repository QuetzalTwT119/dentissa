@php
$rolLabel = ['paciente' => 'Paciente', 'asistente' => 'Asistente', 'admin' => 'Admin'];
$rolClase = [
    'paciente' => 'bg-stone-100 text-stone-700 border border-stone-200/60',
    'asistente' => 'bg-sky-50 text-sky-700 border border-sky-200/60',
    'admin' => 'bg-brand-50 text-brand-700 border border-brand-200/60',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Usuarios
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Cuentas de acceso, credenciales y roles para el equipo de Dentissa.</p>
            </div>
            <a href="{{ route('admin.usuarios.create') }}" class="self-start sm:self-auto">
                <x-primary-button>Nuevo usuario</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e9dfdb]/80 p-6">
                    <p class="text-sm text-[#6b5e62]">
                        Gestiona las cuentas y los roles del personal que accede a la plataforma.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex items-center gap-3">
                            <select name="rol" aria-label="Filtrar por rol" onchange="this.form.submit()"
                                    class="rounded-xl border border-[#e9dfdb] bg-white px-3 py-2 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <option value="">Todos los roles</option>
                                <option value="asistente" @selected(($filtros['rol'] ?? '') === 'asistente')>Asistente</option>
                                <option value="admin" @selected(($filtros['rol'] ?? '') === 'admin')>Admin</option>
                            </select>
                            <select name="activo" aria-label="Filtrar por estado" onchange="this.form.submit()"
                                    class="rounded-xl border border-[#e9dfdb] bg-white px-3 py-2 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
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
                    <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Correo</th>
                            <th class="px-6 py-3.5">Teléfono</th>
                            <th class="px-6 py-3.5">Rol</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e9dfdb]/60">
                        @forelse ($usuarios as $usuario)
                            <tr class="transition hover:bg-[#FAF6F6]/60">
                                <td class="px-6 py-4 font-medium text-[#2D2526]">{{ $usuario->nombre }} {{ $usuario->apellido }}</td>
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $usuario->email }}</td>
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $usuario->telefono }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $rolClase[$usuario->rol->value] }}">
                                        {{ $rolLabel[$usuario->rol->value] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $usuario->activo ? 'bg-[#EAF4EE] text-[#4E8D72] border border-[#4E8D72]/20' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                        {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('admin.usuarios.edit', $usuario) }}" title="Editar" aria-label="Editar usuario"
                                           class="rounded-xl p-2 text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                            <x-icon name="edit" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.usuarios.activo.toggle', $usuario) }}"
                                              onsubmit="return confirm('¿Seguro que quieres {{ $usuario->activo ? 'desactivar' : 'activar' }} a {{ $usuario->nombre }} {{ $usuario->apellido }}?');">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" title="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}" aria-label="{{ $usuario->activo ? 'Desactivar usuario' : 'Activar usuario' }}"
                                                    class="rounded-xl p-2 transition {{ $usuario->activo ? 'text-rose-600 hover:bg-rose-50 hover:text-rose-800' : 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-800' }}">
                                                <x-icon name="power" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-[#8A7A7D]">
                                    No hay usuarios registrados con esos criterios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <x-pagination-summary :items="$usuarios" label="usuarios" />
            </div>
        </div>
    </div>
</x-app-layout>

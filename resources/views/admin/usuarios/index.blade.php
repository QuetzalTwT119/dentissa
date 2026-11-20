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
        <x-admin.header
            badge="Equipo y Accesos"
            title="Usuarios"
            subtitle="Cuentas de acceso, credenciales y roles para el equipo de Dentissa."
        >
            <x-slot:actions>
                <a href="{{ route('admin.usuarios.create') }}">
                    <x-primary-button class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo usuario
                    </x-primary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            <x-admin.card>
                <x-admin.card-header
                    title="Equipo registrado"
                    description="Gestiona las cuentas y los roles del personal que accede a la plataforma."
                >
                    <x-slot:actions>
                        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex flex-wrap items-center gap-2.5">
                            <select name="rol" aria-label="Filtrar por rol" onchange="this.form.submit()"
                                    class="min-h-11 rounded-xl border border-field bg-white px-3 py-1.5 text-sm text-ink shadow-xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <option value="">Todos los roles</option>
                                <option value="asistente" @selected(($filtros['rol'] ?? '') === 'asistente')>Asistente</option>
                                <option value="admin" @selected(($filtros['rol'] ?? '') === 'admin')>Admin</option>
                            </select>
                            <select name="activo" aria-label="Filtrar por estado" onchange="this.form.submit()"
                                    class="min-h-11 rounded-xl border border-field bg-white px-3 py-1.5 text-sm text-ink shadow-xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <option value="">Todos los estados</option>
                                <option value="1" @selected(($filtros['activo'] ?? '') === '1')>Activos</option>
                                <option value="0" @selected(($filtros['activo'] ?? '') === '0')>Inactivos</option>
                            </select>
                        </form>

                        <a href="{{ route('admin.usuarios.create') }}" class="hidden sm:inline-block">
                            <x-secondary-button>Nuevo usuario</x-secondary-button>
                        </a>
                    </x-slot:actions>
                </x-admin.card-header>

                <x-admin.table minWidth="min-w-[760px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Correo</th>
                            <th class="px-6 py-3.5">Teléfono</th>
                            <th class="px-6 py-3.5">Rol</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($usuarios as $usuario)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <x-admin.user-avatar :name="$usuario->nombre . ' ' . $usuario->apellido" />
                                    <span>{{ $usuario->nombre }} {{ $usuario->apellido }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-ink-soft">{{ $usuario->email }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $usuario->telefono ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $rolClase[$usuario->rol->value] ?? 'bg-shell text-ink' }}">
                                    {{ $rolLabel[$usuario->rol->value] ?? $usuario->rol->value }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :active="$usuario->activo" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.usuarios.edit', $usuario) }}" title="Editar" aria-label="Editar usuario"
                                       class="rounded-xl p-2 text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                        <x-icon name="edit" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.usuarios.activo.toggle', $usuario) }}"
                                          onsubmit="return confirm('¿Seguro que quieres {{ $usuario->activo ? 'desactivar' : 'activar' }} a {{ $usuario->nombre }} {{ $usuario->apellido }}?');">
                                        @csrf
                                        @method('patch')
                                        <button type="submit" title="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}" aria-label="{{ $usuario->activo ? 'Desactivar usuario' : 'Activar usuario' }}"
                                                class="rounded-xl p-2 transition {{ $usuario->activo ? 'text-rose-600 hover:bg-rose-50 hover:text-rose-800' : 'text-sage-600 hover:bg-sage-50 hover:text-sage-700' }}">
                                            <x-icon name="power" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="6"
                            title="No hay usuarios registrados con esos criterios"
                            description="Intenta cambiando los filtros de rol o estado para ver más resultados."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$usuarios" label="usuarios" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

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
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            <x-admin.card>


                <x-pagination-summary :items="$usuarios" label="usuarios">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.usuarios.index')" :campos="['rol', 'activo']">
                            <x-filtro.select name="rol" etiqueta="Filtrar por rol" todos="Todos los roles" :opciones="['asistente' => 'Asistente', 'admin' => 'Admin']" />
                            <x-filtro.select name="activo" etiqueta="Filtrar por estado" todos="Todos los estados" :opciones="['1' => 'Activos', '0' => 'Inactivos']" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                    <x-slot:acciones>
                        <a href="{{ route('admin.usuarios.create') }}" class="btn-brand min-h-11 px-5">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /> </svg>
                            Nuevo usuario
                        </a>
                    </x-slot:acciones>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[760px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Correo</th>
                            <th class="px-6 py-3.5">Teléfono</th>
                            <th class="px-6 py-3.5">Rol</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right lg:text-center">Acciones</th>
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
                                <x-chip class="{{ $rolClase[$usuario->rol->value] ?? 'bg-shell text-ink' }}">
                                    {{ $rolLabel[$usuario->rol->value] ?? $usuario->rol->value }}
                                </x-chip>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :active="$usuario->activo" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{--
                                    22/11/2026: el activar/desactivar usa el botón global (antes era cuadrado y
                                    más chico) y en la cuenta propia sale ⚠: el servidor no deja
                                    desactivarse a uno mismo y antes mostraba una página de error.
                                --}}
                                <x-admin.acciones-fila :editar="route('admin.usuarios.edit', $usuario)" que="el usuario" :nombre="$usuario->nombre.' '.$usuario->apellido">
                                    @if ($usuario->is(auth()->user()))
                                        <span class="grid h-11 w-11 place-items-center lg:h-9 lg:w-9" data-cuenta-propia>
                                            <x-ayuda tono="info" etiqueta="Es tu cuenta">No puedes desactivar tu propia cuenta. Otro administrador puede hacerlo.</x-ayuda>
                                        </span>
                                    @else
                                        <x-admin.boton-fila :action="route('admin.usuarios.activo.toggle', $usuario)" icono="power"
                                                            :color="$usuario->activo ? 'rose' : 'sage'" :tono="$usuario->activo ? 'peligro' : 'normal'"
                                                            :etiqueta="($usuario->activo ? 'Desactivar a ' : 'Activar a ').$usuario->nombre.' '.$usuario->apellido"
                                                            :titulo="($usuario->activo ? '¿Desactivar a ' : '¿Activar a ').$usuario->nombre.' '.$usuario->apellido.'?'"
                                                            :mensaje="$usuario->activo ? 'No podrá iniciar sesión hasta que lo vuelvas a activar.' : 'Podrá volver a iniciar sesión con su contraseña.'"
                                                            :boton="$usuario->activo ? 'Sí, desactivar' : 'Sí, activar'" />
                                    @endif
                                </x-admin.acciones-fila>
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

                <x-pagination-summary :items="$usuarios" label="usuarios" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

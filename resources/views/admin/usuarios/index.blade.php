@php
// Etiquetas de RolUsuario::etiqueta() (22/11/2026: super_admin, doctor y asistente).
$rolClase = [
    'super_admin' => 'bg-brand-50 text-brand-700 border border-brand-200/60',
    'doctor' => 'bg-peach-50 text-brand-800 border border-peach-200',
    'asistente' => 'bg-sky-50 text-sky-800 border border-sky-200/60',
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
                            <x-filtro.select name="rol" etiqueta="Filtrar por rol" todos="Todos los roles" :opciones="collect(\App\Enums\RolUsuario::cases())->mapWithKeys(fn ($r) => [$r->value => $r->etiqueta()])->all()" />
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
                                <x-chip class="{{ $rolClase[$usuario->rol->value] ?? 'bg-shell text-ink' }}">
                                    {{ $usuario->rol->etiqueta() }}
                                </x-chip>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{-- Interruptor global (antes etiqueta + botón aparte en Acciones). La cuenta
                                     propia sale bloqueada: el servidor no deja desactivarse a uno mismo. --}}
                                <x-admin.interruptor-estado :action="route('admin.usuarios.activo.toggle', $usuario)" :activo="$usuario->activo" :nombre="$usuario->nombre.' '.$usuario->apellido"
                                                            desactivar="No podrá iniciar sesión hasta que lo vuelvas a activar." activar="Podrá volver a iniciar sesión con su contraseña.">
                                    @if ($usuario->is(auth()->user()))
                                        <x-slot:bloqueo>
                                            <span data-cuenta-propia><x-ayuda tono="info" etiqueta="Es tu cuenta">No puedes desactivarla ni cambiar tu rol. Tus datos y tu contraseña se editan en Mi perfil.</x-ayuda></span>
                                        </x-slot:bloqueo>
                                    @elseif ($usuario->rol === \App\Enums\RolUsuario::SuperAdmin && auth()->user()->rol !== \App\Enums\RolUsuario::SuperAdmin)
                                        {{-- La cuenta titular solo la cambia otra titular (22/11/2026). --}}
                                        <x-slot:bloqueo>
                                            <span data-cuenta-titular><x-ayuda tono="info" etiqueta="Cuenta titular">Solo la doctora titular puede desactivar esta cuenta.</x-ayuda></span>
                                        </x-slot:bloqueo>
                                    @endif
                                </x-admin.interruptor-estado>
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{-- Activar/desactivar vive en la columna Estado (interruptor global). --}}
                                {{--
                                    Solo crear, desactivar (columna Estado) y eliminar (22/11/2026). Sin ✎:
                                    cada quien edita sus datos en Mi perfil. La papelera sale apagada con su
                                    motivo en la cuenta propia y en quien registró consultas clínicas.
                                --}}
                                @php
                                    $motivoNoEliminar = match (true) {
                                        $usuario->is(auth()->user()) => 'Es tu cuenta: no puedes eliminarla.',
                                        $usuario->rol === \App\Enums\RolUsuario::SuperAdmin && auth()->user()->rol !== \App\Enums\RolUsuario::SuperAdmin => 'Cuenta titular: solo la doctora titular puede eliminarla.',
                                        $usuario->consultas_registradas_count > 0 => 'Registró '.$usuario->consultas_registradas_count.' '.($usuario->consultas_registradas_count === 1 ? 'consulta clínica' : 'consultas clínicas').'; eliminarlo las borraría del historial. Desactívalo para conservarlas.',
                                        default => null,
                                    };
                                @endphp
                                <x-admin.acciones-fila :eliminar="route('admin.usuarios.destroy', $usuario)" :bloqueo-eliminar="$motivoNoEliminar"
                                                       confirmar-titulo="¿Eliminar a {{ $usuario->nombre }} {{ $usuario->apellido }}?" que="el usuario" :nombre="$usuario->nombre.' '.$usuario->apellido" />
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

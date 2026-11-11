<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Equipo y Accesos"
            title="Editar usuario"
            subtitle="Modificación de rol, contacto y credenciales de acceso."
        >
            <x-slot:actions>
                <a href="{{ route('admin.usuarios.index') }}" class="hidden sm:inline-block">
                    <x-secondary-button type="button">Volver al listado</x-secondary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-6 sm:p-8">
                <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="nombre" value="Nombre" />
                            <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="old('nombre', $usuario->nombre)" required autofocus />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="apellido" value="Apellido" />
                            <x-text-input id="apellido" name="apellido" class="mt-1.5 block w-full" :value="old('apellido', $usuario->apellido)" required />
                            <x-input-error :messages="$errors->get('apellido')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="email" value="Correo electrónico" />
                        <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email', $usuario->email)" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="telefono" value="Teléfono" />
                        <x-text-input id="telefono" name="telefono" type="tel" class="mt-1.5 block w-full" :value="old('telefono', $usuario->telefono)" required />
                        <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="rol" value="Rol" />
                        <select id="rol" name="rol"
                                class="mt-1.5 block w-full rounded-xl border border-line bg-white px-3.5 py-2.5 text-sm text-ink shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="asistente" @selected(old('rol', $usuario->rol->value) === 'asistente')>Asistente</option>
                            <option value="admin" @selected(old('rol', $usuario->rol->value) === 'admin')>Admin</option>
                        </select>
                        <x-input-error :messages="$errors->get('rol')" class="mt-2" />
                    </div>

                    <div class="relative py-2">
                        <div class="absolute inset-0 flex items-center" aria-hidden="true">
                            <div class="w-full border-t border-line"></div>
                        </div>
                        <div class="relative flex justify-start">
                            <span class="bg-white pr-3 text-xs font-semibold uppercase tracking-wider text-muted">Cambio de contraseña (opcional)</span>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="password" value="Nueva contraseña" />
                        <p class="mb-1 text-xs text-muted">Déjalo en blanco para mantener la contraseña actual.</p>
                        <x-text-input id="password" name="password" type="password" class="mt-1.5 block w-full" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.usuarios.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

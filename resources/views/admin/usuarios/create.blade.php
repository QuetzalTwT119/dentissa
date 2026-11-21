<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Equipo y Accesos"
            title="Nuevo usuario"
            subtitle="Creación de usuario del personal con privilegios de sistema."
        >
            <x-slot:actions>
                <a href="{{ route('admin.usuarios.index') }}" class="hidden sm:inline-block">
                    <x-secondary-button type="button">Volver al listado</x-secondary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-6 sm:p-8">
                <form method="POST" action="{{ route('admin.usuarios.store') }}" data-confirmar="Se guardará y quedará disponible en el panel." data-confirmar-titulo="¿Crear el usuario?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" class="formulario-panel space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label :required="true" for="nombre" value="Nombre" />
                            <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="old('nombre')" required autofocus />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label :required="true" for="apellido" value="Apellido" />
                            <x-text-input id="apellido" name="apellido" class="mt-1.5 block w-full" :value="old('apellido')" required />
                            <x-input-error :messages="$errors->get('apellido')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label :required="true" for="email" value="Correo electrónico" />
                        <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label :required="true" for="telefono" value="Teléfono" />
                        <x-text-input id="telefono" name="telefono" type="tel" class="mt-1.5 block w-full" :value="old('telefono')" required />
                        <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="rol" value="Rol" />
                        <x-select id="rol" name="rol" class="mt-1.5">
                            <option value="asistente" @selected(old('rol', 'asistente') === 'asistente')>Asistente</option>
                            <option value="admin" @selected(old('rol') === 'admin')>Admin</option>
                        </x-select>
                        <x-input-error :messages="$errors->get('rol')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label :required="true" for="password" value="Contraseña" />
                        <x-text-input id="password" name="password" type="password" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label :required="true" for="password_confirmation" value="Confirmar contraseña" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1.5 block w-full" required />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.usuarios.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

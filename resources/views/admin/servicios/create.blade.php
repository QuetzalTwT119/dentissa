<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Nuevo servicio"
            subtitle="Creación de categoría o especialidad de atención."
        >
            <x-slot:actions>
                <a href="{{ route('admin.servicios.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.servicios.store') }}" data-confirmar="Se guardará y quedará disponible en el panel." data-confirmar-titulo="¿Crear el servicio?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" enctype="multipart/form-data" class="formulario-panel space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    <div>
                        <x-input-label :required="true" for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="old('nombre')" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción" />
                        <x-textarea id="descripcion" name="descripcion" rows="3" class="mt-1.5">{{ old('descripcion') }}</x-textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="icono" value="Icono (clase o nombre)" />
                        <x-text-input id="icono" name="icono" class="mt-1.5 block w-full" :value="old('icono')" />
                        <x-input-error :messages="$errors->get('icono')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen representativa" />
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-ink file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>


                    <div class="flex items-center">
                        <x-checkbox id="activo" name="activo" value="1" :checked="old('activo', true)" />
                        <x-input-label for="activo" value="Servicio activo" class="ml-2.5" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.servicios.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

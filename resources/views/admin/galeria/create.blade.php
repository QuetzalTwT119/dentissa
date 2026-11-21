<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Nueva imagen"
            subtitle="Carga de fotografía de instalaciones o consultorio."
        >
            <x-slot:actions>
                <a href="{{ route('admin.galeria.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.galeria.store') }}" data-confirmar="Se guardará y quedará disponible en el panel." data-confirmar-titulo="¿Agregar la foto?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" enctype="multipart/form-data" class="formulario-panel space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    <div>
                        <x-input-label for="titulo" value="Título (opcional)" />
                        <x-text-input id="titulo" name="titulo" class="mt-1.5 block w-full" :value="old('titulo')" autofocus />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción (opcional)" />
                        <x-textarea id="descripcion" name="descripcion" rows="3" class="mt-1.5">{{ old('descripcion') }}</x-textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label :required="true" for="imagen" value="Imagen" />
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-ink file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100" required>
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de visualización" />
                        <x-text-input id="orden" name="orden" type="number" min="0" class="mt-1.5 block w-full" :value="old('orden', 0)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="activa" name="activa" value="1" :checked="old('activa', true)" />
                        <x-input-label for="activa" value="Visible en la galería" class="ml-2.5" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.galeria.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

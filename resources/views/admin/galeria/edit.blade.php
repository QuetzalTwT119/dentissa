<x-app-layout>
    <x-slot name="header">
        <x-admin.header :volver="route('admin.galeria.index')" volver-texto="Volver a Galería"
            title="Editar imagen"
            subtitle="Actualización de foto, título y visibilidad."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.galeria.update', $imagen) }}" data-confirmar="Los cambios se aplicarán de inmediato." data-confirmar-titulo="¿Guardar los cambios de la foto?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal" enctype="multipart/form-data" class="formulario-panel space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="titulo" value="Título (opcional)" />
                        <x-text-input id="titulo" name="titulo" class="mt-1.5 block w-full" :value="old('titulo', $imagen->titulo)" autofocus />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción (opcional)" />
                        <x-textarea id="descripcion" name="descripcion" rows="3" class="mt-1.5">{{ old('descripcion', $imagen->descripcion) }}</x-textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen" />
                        <div class="mt-2 mb-3">
                            <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-28 w-28 rounded-xl border border-line object-cover shadow-2xs">
                        </div>
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-ink file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de visualización" />
                        <x-text-input id="orden" name="orden" type="number" min="0" class="mt-1.5 block w-full" :value="old('orden', $imagen->orden)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="activa" name="activa" value="1" :checked="old('activa', $imagen->activa)" />
                        <x-input-label for="activa" value="Visible en la galería" class="ml-2.5" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.galeria.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

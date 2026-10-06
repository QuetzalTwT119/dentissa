<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Editar imagen
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.galeria.update', $imagen) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="titulo" value="Título (opcional)" />
                        <x-text-input id="titulo" name="titulo" class="mt-1 block w-full" :value="old('titulo', $imagen->titulo)" autofocus />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción (opcional)" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('descripcion', $imagen->descripcion) }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen" />
                        <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="mt-2 h-24 w-24 rounded object-cover">
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1 block w-full text-sm">
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de visualización" />
                        <x-text-input id="orden" name="orden" type="number" min="0" class="mt-1 block w-full" :value="old('orden', $imagen->orden)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="activa" name="activa" value="1" :checked="old('activa', $imagen->activa)" />
                        <x-input-label for="activa" value="Visible en la galería" class="ml-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.galeria.index') }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

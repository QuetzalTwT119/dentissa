<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Nuevo servicio
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.servicios.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" :value="old('nombre')" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('descripcion') }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="icono" value="Icono (clase o nombre)" />
                        <x-text-input id="icono" name="icono" class="mt-1 block w-full" :value="old('icono')" />
                        <x-input-error :messages="$errors->get('icono')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen" />
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1 block w-full text-sm">
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de visualización" />
                        <x-text-input id="orden" name="orden" type="number" class="mt-1 block w-full" :value="old('orden', 0)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="activo" name="activo" value="1" :checked="old('activo', true)" />
                        <x-input-label for="activo" value="Servicio activo" class="ml-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.servicios.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

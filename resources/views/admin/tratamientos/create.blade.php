<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Nuevo tratamiento
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.tratamientos.store') }}" enctype="multipart/form-data" class="space-y-6">
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
                        <x-input-label for="categoria" value="Categoría" />
                        <select id="categoria" name="categoria"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Sin categoría</option>
                            @foreach (['Estética', 'Preventiva', 'Restauradora', 'Quirúrgica'] as $categoria)
                                <option value="{{ $categoria }}" @selected(old('categoria') === $categoria)>{{ $categoria }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('categoria')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="duracion_estimada" value="Duración (minutos)" />
                            <x-text-input id="duracion_estimada" name="duracion_estimada" type="number" class="mt-1 block w-full" :value="old('duracion_estimada')" />
                            <x-input-error :messages="$errors->get('duracion_estimada')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="precio_referencia" value="Precio de referencia" />
                            <x-text-input id="precio_referencia" name="precio_referencia" type="number" step="0.01" class="mt-1 block w-full" :value="old('precio_referencia')" />
                            <x-input-error :messages="$errors->get('precio_referencia')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="sesiones_requeridas" value="Sesiones requeridas" />
                        <x-text-input id="sesiones_requeridas" name="sesiones_requeridas" type="number" min="1" class="mt-1 block w-full" :value="old('sesiones_requeridas', 1)" />
                        <x-input-error :messages="$errors->get('sesiones_requeridas')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen" />
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1 block w-full text-sm">
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="requiere_autorizacion" name="requiere_autorizacion" value="1" :checked="old('requiere_autorizacion')" />
                        <x-input-label for="requiere_autorizacion" value="Requiere autorización previa" class="ml-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="activo" name="activo" value="1" :checked="old('activo', true)" />
                        <x-input-label for="activo" value="Tratamiento activo" class="ml-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.tratamientos.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Nuevo servicio
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Creación de categoría o especialidad de atención.</p>
            </div>
            <a href="{{ route('admin.servicios.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8">
                <form method="POST" action="{{ route('admin.servicios.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="old('nombre')" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition placeholder:text-[#8A7A7D]/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('descripcion') }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="icono" value="Icono (clase o nombre)" />
                        <x-text-input id="icono" name="icono" class="mt-1.5 block w-full" :value="old('icono')" />
                        <x-input-error :messages="$errors->get('icono')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen representativa" />
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-[#2D2526] file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de visualización" />
                        <x-text-input id="orden" name="orden" type="number" class="mt-1.5 block w-full" :value="old('orden', 0)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="activo" name="activo" value="1" :checked="old('activo', true)" />
                        <x-input-label for="activo" value="Servicio activo" class="ml-2.5" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-[#e9dfdb]">
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

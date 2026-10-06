<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Editar servicio
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.servicios.update', $servicio) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" :value="old('nombre', $servicio->nombre)" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('descripcion', $servicio->descripcion) }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="icono" value="Icono (clase o nombre)" />
                        <x-text-input id="icono" name="icono" class="mt-1 block w-full" :value="old('icono', $servicio->icono)" />
                        <x-input-error :messages="$errors->get('icono')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen" />
                        @if ($servicio->imagen)
                            <img src="/storage/{{ $servicio->imagen }}" alt="{{ $servicio->nombre }}" class="mt-2 h-20 w-20 rounded object-cover">
                        @endif
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1 block w-full text-sm">
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de visualización" />
                        <x-text-input id="orden" name="orden" type="number" class="mt-1 block w-full" :value="old('orden', $servicio->orden)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="activo" name="activo" value="1" :checked="old('activo', $servicio->activo)" />
                        <x-input-label for="activo" value="Servicio activo" class="ml-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.servicios.index') }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900">Tratamientos asignados</h3>
                <p class="mt-1 text-sm text-gray-600">Selecciona los tratamientos disponibles para este servicio.</p>

                <form method="POST" action="{{ route('admin.servicios.tratamientos.sync', $servicio) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('patch')

                    @forelse ($tratamientos as $tratamiento)
                        <div class="flex items-center">
                            <x-checkbox id="tratamiento-{{ $tratamiento->id }}" name="tratamiento_ids[]" value="{{ $tratamiento->id }}"
                                        :checked="$tratamientosAsignados->contains($tratamiento->id)" />
                            <x-input-label for="tratamiento-{{ $tratamiento->id }}" value="{{ $tratamiento->nombre }}" class="ml-2" />
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Todavía no hay tratamientos registrados.</p>
                    @endforelse

                    <x-primary-button class="mt-4">Guardar tratamientos</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

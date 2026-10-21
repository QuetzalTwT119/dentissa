<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Editar servicio
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Configuración del servicio y asignación de tratamientos.</p>
            </div>
            <a href="{{ route('admin.servicios.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8">
                <form method="POST" action="{{ route('admin.servicios.update', $servicio) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="old('nombre', $servicio->nombre)" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition placeholder:text-[#8A7A7D]/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('descripcion', $servicio->descripcion) }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="icono" value="Icono (clase o nombre)" />
                        <x-text-input id="icono" name="icono" class="mt-1.5 block w-full" :value="old('icono', $servicio->icono)" />
                        <x-input-error :messages="$errors->get('icono')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen representativa" />
                        @if ($servicio->imagen)
                            <div class="mt-2 mb-3">
                                <img src="/storage/{{ $servicio->imagen }}" alt="{{ $servicio->nombre }}" class="h-20 w-20 rounded-xl border border-[#e9dfdb] object-cover shadow-sm">
                            </div>
                        @endif
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-[#2D2526] file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de visualización" />
                        <x-text-input id="orden" name="orden" type="number" class="mt-1.5 block w-full" :value="old('orden', $servicio->orden)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="activo" name="activo" value="1" :checked="old('activo', $servicio->activo)" />
                        <x-input-label for="activo" value="Servicio activo" class="ml-2.5" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-[#e9dfdb]">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.servicios.index') }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8">
                <h3 class="text-lg font-semibold text-[#2D2526]">Tratamientos asignados</h3>
                <p class="mt-1 text-xs text-[#8A7A7D]">Selecciona los tratamientos disponibles para este servicio en la agenda.</p>

                <form method="POST" action="{{ route('admin.servicios.tratamientos.sync', $servicio) }}" class="mt-6 space-y-3">
                    @csrf
                    @method('patch')

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        @forelse ($tratamientos as $tratamiento)
                            <label for="tratamiento-{{ $tratamiento->id }}" class="flex items-center gap-3 rounded-xl border border-[#e9dfdb] bg-[#FAF6F6]/40 p-3 transition hover:bg-white hover:border-[#f2b0a6] cursor-pointer">
                                <x-checkbox id="tratamiento-{{ $tratamiento->id }}" name="tratamiento_ids[]" value="{{ $tratamiento->id }}"
                                            :checked="$tratamientosAsignados->contains($tratamiento->id)" />
                                <span class="text-sm font-medium text-[#2D2526]">{{ $tratamiento->nombre }}</span>
                            </label>
                        @empty
                            <p class="col-span-full text-sm text-[#8A7A7D]">Todavía no hay tratamientos registrados.</p>
                        @endforelse
                    </div>

                    <div class="pt-4 border-t border-[#e9dfdb]">
                        <x-primary-button>Guardar tratamientos</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

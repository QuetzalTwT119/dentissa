<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Editar caso — {{ $caso->titulo }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.casos.update', $caso) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="titulo" value="Título" />
                        <x-text-input id="titulo" name="titulo" class="mt-1 block w-full" :value="old('titulo', $caso->titulo)" required />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción breve" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('descripcion', $caso->descripcion) }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="orden" value="Orden" />
                            <x-text-input id="orden" name="orden" type="number" min="0" class="mt-1 block w-full" :value="old('orden', $caso->orden)" />
                            <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="activo" value="Estado" />
                            <select id="activo" name="activo"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="1" @selected(old('activo', $caso->activo ? '1' : '0') == '1')>Activo</option>
                                <option value="0" @selected(old('activo', $caso->activo ? '1' : '0') == '0')>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.casos.index') }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900">Fotos (antes/después)</h3>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @forelse ($caso->imagenes as $imagen)
                        <div class="relative">
                            <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-32 w-full rounded-md object-cover">
                            <form method="POST" action="{{ route('admin.casos.imagenes.destroy', [$caso, $imagen]) }}"
                                  onsubmit="return confirm('¿Eliminar esta foto?');" class="absolute right-1 top-1">
                                @csrf
                                @method('delete')
                                <button type="submit" title="Eliminar foto" aria-label="Eliminar foto"
                                        class="rounded-full bg-white/90 p-1.5 text-red-600 shadow hover:bg-white">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="col-span-full text-sm text-gray-500">Todavía no hay fotos para este caso.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.casos.imagenes.store', $caso) }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <x-input-label for="archivo" value="Nueva foto (máx. 2MB)" />
                        <input id="archivo" name="archivo" type="file" accept="image/*" class="mt-1 block w-full text-sm">
                        <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                    </div>
                    <x-primary-button>Subir</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

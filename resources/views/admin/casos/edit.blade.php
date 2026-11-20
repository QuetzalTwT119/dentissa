<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="mt-1.5 text-title-2 text-ink">
                    Editar caso — {{ $caso->titulo }}
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted">Gestión de información y fotografías del caso clínico.</p>
            </div>
            <a href="{{ route('admin.casos.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8">
                <form method="POST" action="{{ route('admin.casos.update', $caso) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="titulo" value="Título" />
                        <x-text-input id="titulo" name="titulo" class="mt-1.5 block w-full" :value="old('titulo', $caso->titulo)" required />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción breve" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition placeholder:text-muted focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('descripcion', $caso->descripcion) }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="orden" value="Orden" />
                            <x-text-input id="orden" name="orden" type="number" min="0" class="mt-1.5 block w-full" :value="old('orden', $caso->orden)" />
                            <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="activo" value="Estado" />
                            <select id="activo" name="activo"
                                    class="min-h-11 mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <option value="1" @selected(old('activo', $caso->activo ? '1' : '0') == '1')>Activo</option>
                                <option value="0" @selected(old('activo', $caso->activo ? '1' : '0') == '0')>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.casos.index') }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>

            <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8">
                <h3 class="text-headline font-semibold text-ink">Fotos (antes/después)</h3>
                <p class="mt-1 text-xs text-muted">Imágenes comparativas del caso para la galería pública.</p>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @forelse ($caso->imagenes as $imagen)
                        <div class="relative overflow-hidden rounded-xl border border-line shadow-2xs group">
                            <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-32 w-full object-cover">
                            <form method="POST" action="{{ route('admin.casos.imagenes.destroy', [$caso, $imagen]) }}"
                                  onsubmit="return confirm('¿Eliminar esta foto?');" class="absolute right-2 top-2">
                                @csrf
                                @method('delete')
                                <button type="submit" title="Eliminar foto" aria-label="Eliminar foto"
                                        class="rounded-full bg-white/90 p-1.5 text-rose-600 shadow transition hover:bg-white hover:text-rose-800">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="col-span-full rounded-xl border border-dashed border-line p-4 text-center text-sm text-muted">Todavía no hay fotos para este caso.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.casos.imagenes.store', $caso) }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4 border-t border-line pt-5">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <x-input-label for="archivo" value="Nueva foto (máx. 2MB)" />
                        <input id="archivo" name="archivo" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-ink file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                    </div>
                    <x-primary-button>Subir</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Editar caso — {{ $caso->titulo }}"
            subtitle="Gestión de información y fotografías del caso clínico."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.casos.update', $caso) }}" data-confirmar="Los cambios se aplicarán de inmediato." data-confirmar-titulo="¿Guardar los cambios del caso?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal" class="formulario-panel space-y-6">
                    @csrf
                    @method('put')

                    <x-form-obligatorios />
                    <div>
                        <x-input-label :required="true" for="titulo" value="Título" />
                        <x-text-input id="titulo" name="titulo" class="mt-1.5 block w-full" :value="old('titulo', $caso->titulo)" required />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción breve" />
                        <x-textarea id="descripcion" name="descripcion" rows="3" class="mt-1.5">{{ old('descripcion', $caso->descripcion) }}</x-textarea>
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
                            <x-select id="activo" name="activo" class="mt-1.5">
                                <option value="1" @selected(old('activo', $caso->activo ? '1' : '0') == '1')>Activo</option>
                                <option value="0" @selected(old('activo', $caso->activo ? '1' : '0') == '0')>Inactivo</option>
                            </x-select>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <x-boton-cancelar href="{{ route('admin.casos.index') }}" />
                    </div>
                </form>
            </x-admin.card>

            <x-admin.card class="p-5 sm:p-8">
                <h3 class="text-headline font-semibold text-ink">Fotos (antes/después)</h3>
                <p class="mt-1 text-xs text-muted">Imágenes comparativas del caso para la galería pública.</p>

                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @forelse ($caso->imagenes as $imagen)
                        <div class="relative overflow-hidden rounded-xl border border-line shadow-2xs group">
                            <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-32 w-full object-cover">
                            <form method="POST" action="{{ route('admin.casos.imagenes.destroy', [$caso, $imagen]) }}"
                                  data-confirmar="Esta acción no se puede deshacer." data-confirmar-titulo="¿Eliminar esta foto?" data-confirmar-boton="Sí, eliminar" data-confirmar-tono="peligro" class="absolute right-2 top-2">
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

                <form method="POST" action="{{ route('admin.casos.imagenes.store', $caso) }}" data-confirmar="La foto se agregará al caso." data-confirmar-titulo="¿Subir la foto?" data-confirmar-boton="Sí, subir" data-confirmar-tono="normal" enctype="multipart/form-data" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4 border-t border-line pt-5">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <x-input-label for="archivo" value="Nueva foto (máx. 2MB)" />
                        <input id="archivo" name="archivo" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-ink file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                    </div>
                    <x-primary-button>Subir</x-primary-button>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

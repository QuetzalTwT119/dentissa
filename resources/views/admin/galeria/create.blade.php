<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="mt-1.5 text-title-2 text-ink">
                    Nueva imagen
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted">Carga de fotografía de instalaciones o consultorio.</p>
            </div>
            <a href="{{ route('admin.galeria.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8">
                <form method="POST" action="{{ route('admin.galeria.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="titulo" value="Título (opcional)" />
                        <x-text-input id="titulo" name="titulo" class="mt-1.5 block w-full" :value="old('titulo')" autofocus />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción (opcional)" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition placeholder:text-muted focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('descripcion') }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen" />
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-ink file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100" required>
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de visualización" />
                        <x-text-input id="orden" name="orden" type="number" min="0" class="mt-1.5 block w-full" :value="old('orden', 0)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center">
                        <x-checkbox id="activa" name="activa" value="1" :checked="old('activa', true)" />
                        <x-input-label for="activa" value="Visible en la galería" class="ml-2.5" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.galeria.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

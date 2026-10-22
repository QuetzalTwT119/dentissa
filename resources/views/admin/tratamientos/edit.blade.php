<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Editar tratamiento
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Ajuste de tiempos clínicos, precios y requisitos.</p>
            </div>
            <a href="{{ route('admin.tratamientos.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8">
                <form method="POST" action="{{ route('admin.tratamientos.update', $tratamiento) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('put')

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="old('nombre', $tratamiento->nombre)" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition placeholder:text-[#8A7A7D]/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('descripcion', $tratamiento->descripcion) }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="categoria" value="Categoría" />
                        <select id="categoria" name="categoria"
                                class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">Sin categoría</option>
                            @foreach (['Estética', 'Preventiva', 'Restauradora', 'Quirúrgica'] as $categoria)
                                <option value="{{ $categoria }}" @selected(old('categoria', $tratamiento->categoria) === $categoria)>{{ $categoria }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('categoria')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <x-input-label for="duracion_estimada" value="Duración (minutos)" />
                            <x-text-input id="duracion_estimada" name="duracion_estimada" type="number" class="mt-1.5 block w-full" :value="old('duracion_estimada', $tratamiento->duracion_estimada)" />
                            <x-input-error :messages="$errors->get('duracion_estimada')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="precio_referencia" value="Precio de referencia" />
                            <x-text-input id="precio_referencia" name="precio_referencia" type="number" step="0.01" class="mt-1.5 block w-full" :value="old('precio_referencia', $tratamiento->precio_referencia)" />
                            <x-input-error :messages="$errors->get('precio_referencia')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="sesiones_requeridas" value="Sesiones requeridas" />
                        <x-text-input id="sesiones_requeridas" name="sesiones_requeridas" type="number" min="1" class="mt-1.5 block w-full" :value="old('sesiones_requeridas', $tratamiento->sesiones_requeridas)" />
                        <x-input-error :messages="$errors->get('sesiones_requeridas')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="imagen" value="Imagen explicativa" />
                        @if ($tratamiento->imagen)
                            <div class="mt-2 mb-3">
                                <img src="/storage/{{ $tratamiento->imagen }}" alt="{{ $tratamiento->nombre }}" class="h-20 w-20 rounded-xl border border-[#e9dfdb] object-cover shadow-sm">
                            </div>
                        @endif
                        <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-[#2D2526] file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                    </div>

                    <div class="space-y-3 pt-2">
                        <div class="flex items-center">
                            <x-checkbox id="requiere_autorizacion" name="requiere_autorizacion" value="1" :checked="old('requiere_autorizacion', $tratamiento->requiere_autorizacion)" />
                            <x-input-label for="requiere_autorizacion" value="Requiere autorización previa" class="ml-2.5" />
                        </div>

                        <div class="flex items-center">
                            <x-checkbox id="activo" name="activo" value="1" :checked="old('activo', $tratamiento->activo)" />
                            <x-input-label for="activo" value="Tratamiento activo" class="ml-2.5" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-[#e9dfdb]">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.tratamientos.index') }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

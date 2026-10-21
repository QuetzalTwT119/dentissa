<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Nuevo caso
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Creación de caso clínico demostrativo de antes y después.</p>
            </div>
            <a href="{{ route('admin.casos.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8">
                <p class="text-sm text-[#6b5e62]">
                    Después de guardar podrás subir las fotos de antes/después desde la pantalla de edición.
                </p>

                <form method="POST" action="{{ route('admin.casos.store') }}" class="mt-6 space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="titulo" value="Título" />
                        <x-text-input id="titulo" name="titulo" class="mt-1.5 block w-full" :value="old('titulo')" placeholder="Ej. Ortodoncia estética" required />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción breve" />
                        <textarea id="descripcion" name="descripcion" rows="3" placeholder="Ej. Tras el tratamiento de alineación, se logró una sonrisa simétrica y funcional."
                                  class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition placeholder:text-[#8A7A7D]/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('descripcion') }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de despliegue" />
                        <x-text-input id="orden" name="orden" type="number" min="0" class="mt-1.5 block w-full" :value="old('orden', 0)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-[#e9dfdb]">
                        <x-primary-button>Guardar y continuar</x-primary-button>

                        <a href="{{ route('admin.casos.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

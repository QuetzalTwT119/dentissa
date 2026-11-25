<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Nuevo caso"
            subtitle="Creación de caso clínico demostrativo de antes y después."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <p class="text-sm text-ink-soft">
                    Después de guardar podrás subir las fotos de antes/después desde la pantalla de edición.
                </p>

                <form method="POST" action="{{ route('admin.casos.store') }}" data-confirmar="Se guardará y quedará disponible en el panel." data-confirmar-titulo="¿Crear el caso?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" class="formulario-panel mt-6 space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    <div>
                        <x-input-label :required="true" for="titulo" value="Título" />
                        <x-text-input id="titulo" name="titulo" class="mt-1.5 block w-full" :value="old('titulo')" placeholder="Ej. Ortodoncia estética" required />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="descripcion" value="Descripción breve" />
                        <x-textarea id="descripcion" name="descripcion" rows="3" placeholder="Ej. Tras el tratamiento de alineación, se logró una sonrisa simétrica y funcional." class="mt-1.5">{{ old('descripcion') }}</x-textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de despliegue" />
                        <x-text-input id="orden" name="orden" type="number" min="0" class="mt-1.5 block w-full" :value="old('orden', 0)" />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar y continuar</x-primary-button>

                        <x-boton-cancelar href="{{ route('admin.casos.index') }}" />
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

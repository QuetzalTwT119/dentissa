<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Nueva pregunta frecuente"
            subtitle="Alta de pregunta y respuesta oficial del consultorio."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.preguntas-frecuentes.store') }}" data-confirmar="Se guardará y quedará disponible en el panel." data-confirmar-titulo="¿Crear la pregunta?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" class="formulario-panel space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    @include('admin.preguntas-frecuentes._campos', ['pregunta' => null])

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.preguntas-frecuentes.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

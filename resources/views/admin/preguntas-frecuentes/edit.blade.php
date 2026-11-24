<x-app-layout>
    <x-slot name="header">
        <x-admin.header :volver="route('admin.preguntas-frecuentes.index')" volver-texto="Volver a Preguntas frecuentes"
            title="Editar pregunta frecuente"
            subtitle="Modificación de respuesta y variantes de lenguaje natural."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.preguntas-frecuentes.update', $pregunta) }}" data-confirmar="Los cambios se aplicarán de inmediato." data-confirmar-titulo="¿Guardar los cambios de la pregunta?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal" class="formulario-panel space-y-6">
                    @csrf
                    @method('put')

                    <x-form-obligatorios />
                    @include('admin.preguntas-frecuentes._campos')

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.preguntas-frecuentes.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

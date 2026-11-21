<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Nueva oferta"
            subtitle="Creación de paquete promocional o descuento temporal."
        >
            <x-slot:actions>
                <a href="{{ route('admin.ofertas.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.ofertas.store') }}" data-confirmar="Se guardará y quedará disponible en el panel." data-confirmar-titulo="¿Crear la oferta?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" enctype="multipart/form-data" class="formulario-panel formulario-3 space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    @include('admin.ofertas._campos', ['oferta' => null])

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.ofertas.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

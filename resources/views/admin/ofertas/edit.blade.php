<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Editar oferta"
            subtitle="Ajuste de cupos, vigencia y precios de promoción."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.ofertas.update', $oferta) }}" data-confirmar="Los cambios se aplicarán de inmediato." data-confirmar-titulo="¿Guardar los cambios de la oferta?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal" enctype="multipart/form-data" class="formulario-panel formulario-3 space-y-6">
                    @csrf
                    @method('put')

                    <x-form-obligatorios />
                    @include('admin.ofertas._campos')

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <x-boton-cancelar href="{{ route('admin.ofertas.index') }}" />
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

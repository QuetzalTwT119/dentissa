<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="mt-1.5 text-title-2 text-ink">
                    Nueva oferta
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted">Creación de paquete promocional o descuento temporal.</p>
            </div>
            <a href="{{ route('admin.ofertas.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8">
                <form method="POST" action="{{ route('admin.ofertas.store') }}" enctype="multipart/form-data" class="space-y-6">
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
            </div>
        </div>
    </div>
</x-app-layout>

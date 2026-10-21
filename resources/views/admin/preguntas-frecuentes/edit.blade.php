<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Editar pregunta frecuente
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Modificación de respuesta y variantes de lenguaje natural.</p>
            </div>
            <a href="{{ route('admin.preguntas-frecuentes.index') }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al listado</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8">
                <form method="POST" action="{{ route('admin.preguntas-frecuentes.update', $pregunta) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    @include('admin.preguntas-frecuentes._campos')

                    <div class="flex items-center gap-4 pt-4 border-t border-[#e9dfdb]">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.preguntas-frecuentes.index') }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Editar pregunta frecuente
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.preguntas-frecuentes.update', $pregunta) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    @include('admin.preguntas-frecuentes._campos')

                    <div class="flex items-center gap-4">
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

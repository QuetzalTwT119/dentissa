<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Nueva pregunta frecuente
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.preguntas-frecuentes.store') }}" class="space-y-6">
                    @csrf

                    @include('admin.preguntas-frecuentes._campos', ['pregunta' => null])

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar</x-primary-button>

                        <a href="{{ route('admin.preguntas-frecuentes.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

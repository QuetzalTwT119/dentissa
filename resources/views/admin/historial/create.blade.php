<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Nueva consulta — {{ $paciente->nombre }} {{ $paciente->apellido }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.pacientes.historial.store', $paciente) }}" class="space-y-6">
                    @csrf

                    @include('admin.historial._campos', ['historial' => null])

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar consulta</x-primary-button>

                        <a href="{{ route('admin.pacientes.historial.index', $paciente) }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

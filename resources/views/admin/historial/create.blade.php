<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Nueva consulta — {{ $paciente->nombre }} {{ $paciente->apellido }}"
            subtitle="Registro de atención médica, diagnóstico y prescripción."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            {{-- Alergias y riesgos antes de escribir la consulta (22/11/2026). --}}
            <x-aviso-clinico :historia="$paciente->historiaClinica" class="mb-6" />
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.pacientes.historial.store', $paciente) }}" data-confirmar="Se guardará en el historial del paciente." data-confirmar-titulo="¿Crear el registro?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" class="formulario-panel formulario-una-columna space-y-6">
                    @csrf

                    <x-form-obligatorios />
                    @include('admin.historial._campos', ['historial' => null])

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar consulta</x-primary-button>

                        <a href="{{ route('admin.pacientes.historial.index', $paciente) }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>

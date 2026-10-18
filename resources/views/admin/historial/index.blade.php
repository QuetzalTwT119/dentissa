@php
$tipoConsultaLabel = [
    'primera_vez' => 'Primera vez',
    'seguimiento' => 'Seguimiento',
    'urgencia' => 'Urgencia',
    'control' => 'Control',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Historial de {{ $paciente->nombre }} {{ $paciente->apellido }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-between">
                <a href="{{ route('admin.pacientes.index') }}">
                    <x-secondary-button type="button">← Pacientes</x-secondary-button>
                </a>

                <div class="flex gap-3">
                    @if ($consultas->isNotEmpty())
                        <a href="{{ route('admin.pacientes.historial.pdf', $paciente) }}">
                            <x-secondary-button type="button">Descargar PDF</x-secondary-button>
                        </a>
                    @else
                        <x-secondary-button type="button" disabled title="Disponible después de registrar una consulta">
                            Descargar PDF
                        </x-secondary-button>
                    @endif
                    <a href="{{ route('admin.pacientes.historial.create', $paciente) }}">
                        <x-primary-button>Nueva consulta</x-primary-button>
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div role="status" class="mb-4 rounded-md bg-blue-50 p-4 text-sm text-blue-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="space-y-4">
                @forelse ($consultas as $consulta)
                    <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm text-gray-500">
                                    {{ \Illuminate\Support\Carbon::parse($consulta->fecha_consulta)->translatedFormat('d M Y') }}
                                    · {{ $tipoConsultaLabel[$consulta->tipo_consulta->value] ?? $consulta->tipo_consulta->value }}
                                </p>
                                <p class="mt-1 font-medium text-gray-900">{{ $consulta->motivo_consulta }}</p>
                                @if ($consulta->diagnostico)
                                    <p class="mt-1 text-sm text-gray-600">
                                        <span class="font-medium">Diagnóstico:</span> {{ $consulta->diagnostico }}
                                    </p>
                                @endif
                                <p class="mt-2 text-xs text-gray-400">
                                    Doctor: {{ $consulta->doctor->nombre ?? '' }} {{ $consulta->doctor->apellido ?? '' }}
                                    @if ($consulta->imagenes->isNotEmpty())
                                        · {{ $consulta->imagenes->count() }} adjunto(s)
                                    @endif
                                </p>
                            </div>

                            <div class="flex shrink-0 gap-3">
                                <a href="{{ route('admin.pacientes.historial.edit', [$paciente, $consulta]) }}" class="text-sm text-brand-600 hover:text-brand-900">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('admin.pacientes.historial.destroy', [$paciente, $consulta]) }}"
                                      onsubmit="return confirm('¿Eliminar esta consulta del historial? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-900">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white p-6 text-center text-sm text-gray-500 shadow-sm sm:rounded-lg">
                        Este paciente todavía no tiene consultas registradas.
                        <p class="mt-1">El PDF estará disponible cuando registres la primera consulta.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>

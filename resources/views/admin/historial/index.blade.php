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
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Historial de {{ $paciente->nombre }} {{ $paciente->apellido }}
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Expediente clínico dental cronológico y adjuntos radiográficos.</p>
            </div>
            <a href="{{ route('admin.pacientes.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-[#8A7A7D] hover:text-brand-600 transition">
                ← Volver a Pacientes
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <a href="{{ route('admin.pacientes.index') }}">
                    <x-secondary-button type="button">← Pacientes</x-secondary-button>
                </a>

                <div class="flex flex-wrap items-center gap-3">
                    @if ($consultas->isNotEmpty())
                        <a href="{{ route('admin.pacientes.historial.pdf', $paciente) }}">
                            <x-secondary-button type="button">Descargar PDF</x-secondary-button>
                        </a>
                    @else
                        <x-secondary-button type="button" disabled title="Disponible después de registrar una consulta" class="opacity-50 cursor-not-allowed">
                            Descargar PDF
                        </x-secondary-button>
                    @endif
                    <a href="{{ route('admin.pacientes.historial.create', $paciente) }}">
                        <x-primary-button>Nueva consulta</x-primary-button>
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div role="status" class="mb-6 rounded-2xl border border-sky-200 bg-sky-50/80 p-4 text-sm text-sky-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="space-y-4">
                @forelse ($consultas as $consulta)
                    <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft transition hover:border-[#f2b0a6]">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 border border-brand-200/50">
                                        {{ $tipoConsultaLabel[$consulta->tipo_consulta->value] ?? $consulta->tipo_consulta->value }}
                                    </span>
                                    <p class="text-xs text-[#8A7A7D]">
                                        {{ \Illuminate\Support\Carbon::parse($consulta->fecha_consulta)->translatedFormat('d M Y') }}
                                    </p>
                                </div>
                                <h3 class="mt-2 text-base font-semibold text-[#2D2526]">{{ $consulta->motivo_consulta }}</h3>
                                @if ($consulta->diagnostico)
                                    <p class="mt-1 text-sm text-[#6b5e62]">
                                        <span class="font-medium text-[#2D2526]">Diagnóstico:</span> {{ $consulta->diagnostico }}
                                    </p>
                                @endif
                                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-[#8A7A7D]">
                                    <span>Doctor: {{ $consulta->doctor->nombre ?? '' }} {{ $consulta->doctor->apellido ?? '' }}</span>
                                    @if ($consulta->imagenes->isNotEmpty())
                                        <span>· {{ $consulta->imagenes->count() }} adjunto(s)</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2 pt-2 sm:pt-0 border-t border-[#e9dfdb]/60 sm:border-0">
                                <a href="{{ route('admin.pacientes.historial.edit', [$paciente, $consulta]) }}" class="rounded-xl px-3 py-1.5 text-sm font-medium text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('admin.pacientes.historial.destroy', [$paciente, $consulta]) }}"
                                      onsubmit="return confirm('¿Eliminar esta consulta del historial? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="rounded-xl px-3 py-1.5 text-sm font-medium text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-[#e9dfdb] bg-white p-10 text-center text-sm text-[#8A7A7D] shadow-soft">
                        <p class="font-medium text-[#2D2526]">Este paciente todavía no tiene consultas registradas.</p>
                        <p class="mt-1">El PDF estará disponible cuando registres la primera consulta.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>

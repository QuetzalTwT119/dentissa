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
        <x-admin.header
            badge="Historial Clínico"
            title="Historial de {{ $paciente->nombre }} {{ $paciente->apellido }}"
            subtitle="Expediente clínico dental cronológico y adjuntos radiográficos."
        >
            <x-slot:actions>
                <a href="{{ route('admin.pacientes.index') }}">
                    <x-secondary-button class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver a Pacientes
                    </x-secondary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <a href="{{ route('admin.pacientes.index') }}" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-800 transition">
                    ← Volver a Pacientes
                </a>

                <div class="flex flex-wrap items-center gap-3 ml-auto">
                    @if ($consultas->isNotEmpty())
                        <a href="{{ route('admin.pacientes.historial.pdf', $paciente) }}">
                            <x-secondary-button type="button" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Descargar PDF
                            </x-secondary-button>
                        </a>
                    @else
                        <x-secondary-button type="button" disabled title="Disponible después de registrar una consulta" class="opacity-50 cursor-not-allowed inline-flex items-center gap-2">
                            <svg class="h-4 w-4 text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Descargar PDF
                        </x-secondary-button>
                    @endif
                    <a href="{{ route('admin.pacientes.historial.create', $paciente) }}">
                        <x-primary-button class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nueva consulta
                        </x-primary-button>
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div role="status" class="rounded-2xl border border-sky-200 bg-sky-50/80 p-4 text-sm text-sky-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="space-y-4">
                @forelse ($consultas as $consulta)
                    <div class="rounded-2xl border border-line bg-white p-6 shadow-soft transition hover:border-peach-300">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 border border-brand-200/50">
                                        {{ $tipoConsultaLabel[$consulta->tipo_consulta->value] ?? $consulta->tipo_consulta->value }}
                                    </span>
                                    <p class="text-xs text-muted">
                                        {{ \Illuminate\Support\Carbon::parse($consulta->fecha_consulta)->translatedFormat('d M Y') }}
                                    </p>
                                </div>
                                <h3 class="mt-2 text-headline font-semibold text-ink">{{ $consulta->motivo_consulta }}</h3>
                                @if ($consulta->diagnostico)
                                    <p class="mt-1 text-sm text-ink-soft">
                                        <span class="font-semibold text-ink">Diagnóstico:</span> {{ $consulta->diagnostico }}
                                    </p>
                                @endif
                                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-muted">
                                    <span>Doctor: <strong class="text-ink font-medium">{{ $consulta->doctor->nombre ?? '' }} {{ $consulta->doctor->apellido ?? '' }}</strong></span>
                                    @if ($consulta->imagenes->isNotEmpty())
                                        <span>· <strong class="text-ink font-medium">{{ $consulta->imagenes->count() }}</strong> adjunto(s)</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2 pt-2 sm:pt-0 border-t border-line/60 sm:border-0">
                                <a href="{{ route('admin.pacientes.historial.edit', [$paciente, $consulta]) }}" class="rounded-xl px-3 py-1.5 text-sm font-semibold text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('admin.pacientes.historial.destroy', [$paciente, $consulta]) }}"
                                      onsubmit="return confirm('¿Eliminar esta consulta del historial? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="rounded-xl px-3 py-1.5 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-line bg-white p-10 text-center text-sm text-muted shadow-soft">
                        <p class="font-semibold text-ink">Este paciente todavía no tiene consultas registradas.</p>
                        <p class="mt-1">El PDF estará disponible cuando registres la primera consulta.</p>
                    </div>
                @endforelse
            </div>
            <x-pagination-summary :items="$consultas" label="consultas" :integrated="false" />
        </div>
    </div>
</x-app-layout>

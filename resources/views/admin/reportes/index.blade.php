@php
$estadoLabel = [
    'agendada' => 'Agendada',
    'confirmada' => 'Confirmada',
    'en_curso' => 'En curso',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
    'no_asistio' => 'No asistió',
];
$tipoConsultaLabel = [
    'primera_vez' => 'Primera vez',
    'seguimiento' => 'Seguimiento',
    'urgencia' => 'Urgencia',
    'control' => 'Control',
];
$queryString = '?fecha_inicio='.$reporte['rango']['fecha_inicio'].'&fecha_fin='.$reporte['rango']['fecha_fin'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Métricas y Desempeño"
            title="Reportes administrativos"
            subtitle="Métricas operativas, ocupación, eficiencia clínica y demanda del consultorio."
        >
            <x-slot:actions>
                <a href="{{ route('admin.reportes.pdf').$queryString }}">
                    <x-secondary-button type="button" class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar PDF
                    </x-secondary-button>
                </a>
                <a href="{{ route('admin.reportes.excel').$queryString }}">
                    <x-secondary-button type="button" class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4 text-sage-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar Excel
                    </x-secondary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <!-- Tarjeta de Filtro de Fechas -->
            <x-admin.card>
                <div class="p-6 bg-gradient-to-r from-white via-white to-cream/50">
                    <form method="GET" action="{{ route('admin.reportes.index') }}" class="flex flex-wrap items-end gap-4">
                        <div>
                            <x-input-label for="fecha_inicio" value="Desde" class="text-xs font-semibold uppercase tracking-wider text-muted" />
                            <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1.5" max="{{ now()->toDateString() }}" value="{{ old('fecha_inicio', $reporte['rango']['fecha_inicio']) }}" />
                            <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="fecha_fin" value="Hasta" class="text-xs font-semibold uppercase tracking-wider text-muted" />
                            <x-text-input id="fecha_fin" name="fecha_fin" type="date" class="mt-1.5" max="{{ now()->toDateString() }}" value="{{ old('fecha_fin', $reporte['rango']['fecha_fin']) }}" />
                            <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
                        </div>

                        <x-primary-button>Filtrar</x-primary-button>

                        <div class="ms-auto hidden sm:flex gap-3">
                            <a href="{{ route('admin.reportes.pdf').$queryString }}">
                                <x-secondary-button type="button">Descargar PDF</x-secondary-button>
                            </a>
                            <a href="{{ route('admin.reportes.excel').$queryString }}">
                                <x-secondary-button type="button">Descargar Excel</x-secondary-button>
                            </a>
                        </div>
                    </form>
                </div>
            </x-admin.card>

            <!-- Tarjetas de Métricas Clave (KPIs) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 items-stretch">
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted">Tasa de ocupación</p>
                    <p class="mt-2 font-serif text-3xl font-bold text-brand-600">{{ $reporte['ocupacion']['tasa_ocupacion'] }}%</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted">Tasa de inasistencia</p>
                    <p class="mt-2 font-serif text-3xl font-bold text-brand-600">{{ $reporte['citas']['tasa_inasistencia'] }}%</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted">Eficiencia (citas/1.5h)</p>
                    <p class="mt-2 font-serif text-3xl font-bold text-brand-600">{{ $reporte['eficiencia']['eficiencia'] }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted">Citas completadas por hora</p>
                    <p class="mt-2 font-serif text-3xl font-bold text-brand-600">{{ $reporte['eficiencia']['citas_por_hora'] }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full sm:col-span-2 lg:col-span-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted">Satisfacción promedio</p>
                    <p class="mt-2 font-serif text-3xl font-bold text-brand-600">{{ $reporte['satisfaccion'] ?? 'Sin datos' }}</p>
                </div>
            </div>

            <!-- Detalle por Secciones -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-line bg-white p-6 shadow-soft">
                    <h3 class="mb-4 text-base font-bold text-ink">Pacientes</h3>
                    <div class="space-y-3 rounded-xl border border-line/80 bg-cream/50 p-4 text-sm">
                        <p class="text-ink-soft">Total histórico registrado: <span class="font-bold text-ink">{{ $reporte['pacientes']['total'] }}</span></p>
                        <p class="text-ink-soft">Nuevos en el período: <span class="font-bold text-ink">{{ $reporte['pacientes']['nuevos'] }}</span></p>
                    </div>
                </div>

                <div class="rounded-2xl border border-line bg-white p-6 shadow-soft">
                    <h3 class="mb-4 text-base font-bold text-ink">
                        Citas por estado ({{ $reporte['citas']['total'] }} en el período)
                    </h3>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-line/60">
                            @foreach ($reporte['citas']['por_estado'] as $estado => $total)
                                <tr class="transition hover:bg-cream/60">
                                    <td class="py-2.5 text-ink-soft">{{ $estadoLabel[$estado] ?? $estado }}</td>
                                    <td class="py-2.5 text-right font-bold text-ink">{{ $total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="rounded-2xl border border-line bg-white p-6 shadow-soft">
                    <h3 class="mb-4 text-base font-bold text-ink">Tratamientos más solicitados</h3>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-line/60">
                            @forelse ($reporte['catalogo']['tratamientos_mas_solicitados'] as $fila)
                                <tr class="transition hover:bg-cream/60">
                                    <td class="py-2.5 text-ink-soft">{{ $fila['nombre'] }}</td>
                                    <td class="py-2.5 text-right font-bold text-ink">{{ $fila['total'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-3 text-center text-muted">Sin citas en el período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <p class="mt-4 border-t border-line/60 pt-3 text-xs text-muted">
                        Catálogo actual: {{ $reporte['catalogo']['servicios_activos'] }} servicios activos · {{ $reporte['catalogo']['tratamientos_activos'] }} tratamientos activos
                    </p>
                </div>

                <div class="rounded-2xl border border-line bg-white p-6 shadow-soft">
                    <h3 class="mb-4 text-base font-bold text-ink">
                        Historial clínico ({{ $reporte['historial']['total'] }} {{ $reporte['historial']['total'] === 1 ? 'consulta' : 'consultas' }})
                    </h3>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-line/60">
                            @foreach ($reporte['historial']['por_tipo'] as $tipo => $total)
                                <tr class="transition hover:bg-cream/60">
                                    <td class="py-2.5 text-ink-soft">{{ $tipoConsultaLabel[$tipo] ?? $tipo }}</td>
                                    <td class="py-2.5 text-right font-bold text-ink">{{ $total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

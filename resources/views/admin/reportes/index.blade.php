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
        />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:space-y-4 lg:px-8">
            <!-- Tarjeta de Filtro de Fechas -->
            <x-admin.card>
                <div class="p-6 bg-gradient-to-r from-white via-white to-cream/50 lg:px-6 lg:py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        {{-- Filtro global de fechas (components/filtro): se aplica solo al cambiar la fecha. --}}
                        <x-filtro.barra :action="route('admin.reportes.index')" :campos="['fecha_inicio', 'fecha_fin']">
                            <x-filtro.fecha name="fecha_inicio" etiqueta="Desde" :valor="old('fecha_inicio', $reporte['rango']['fecha_inicio'])" :max="now()->toDateString()" />
                            <x-filtro.fecha name="fecha_fin" etiqueta="Hasta" :valor="old('fecha_fin', $reporte['rango']['fecha_fin'])" :max="now()->toDateString()" />
                        </x-filtro.barra>
                        {{-- Las descargas respetan el rango elegido. --}}
                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                                <x-boton-enlace href="{{ route('admin.reportes.pdf').$queryString }}" class="inline-flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Descargar PDF
                                    </x-boton-enlace>
                                <x-boton-enlace href="{{ route('admin.reportes.excel').$queryString }}" class="inline-flex items-center gap-1.5">
                                        <svg class="h-4 w-4 text-sage-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        Descargar Excel
                                    </x-boton-enlace>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
                    <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
                </div>
            </x-admin.card>

            <!-- Tarjetas de Métricas Clave (KPIs) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 items-stretch">
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full lg:p-4">
                    <p class="text-xs font-semibold uppercase tracking-label text-muted">Tasa de ocupación</p>
                    <p class="mt-2 font-serif text-3xl font-semibold text-brand-600 lg:mt-1 lg:text-2xl">{{ $reporte['ocupacion']['tasa_ocupacion'] }}%</p>
                    {{-- 25/11/2026: se explica qué mide (antes un número suelto que no cuadraba). --}}
                    <p class="mt-1 text-xs text-muted" data-ocupacion-detalle>{{ number_format(intdiv($reporte['ocupacion']['minutos_ocupados'] ?? 0, 60)) }} h con citas de {{ number_format(intdiv($reporte['ocupacion']['capacidad_total'], 60)) }} h de atención</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full lg:p-4">
                    <p class="text-xs font-semibold uppercase tracking-label text-muted">Tasa de inasistencia</p>
                    <p class="mt-2 font-serif text-3xl font-semibold text-brand-600 lg:mt-1 lg:text-2xl">{{ $reporte['citas']['tasa_inasistencia'] }}%</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full lg:p-4">
                    <p class="text-xs font-semibold uppercase tracking-label text-muted">Eficiencia (citas/1.5h)</p>
                    <p class="mt-2 font-serif text-3xl font-semibold text-brand-600 lg:mt-1 lg:text-2xl">{{ $reporte['eficiencia']['eficiencia'] }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full lg:p-4">
                    <p class="text-xs font-semibold uppercase tracking-label text-muted">Citas completadas por hora</p>
                    <p class="mt-2 font-serif text-3xl font-semibold text-brand-600 lg:mt-1 lg:text-2xl">{{ $reporte['eficiencia']['citas_por_hora'] }}</p>
                </div>
                <div class="rounded-2xl border border-line bg-white p-5 shadow-soft transition hover:border-peach-300 flex flex-col justify-between h-full lg:p-4 sm:col-span-2 lg:col-span-1">
                    <p class="text-xs font-semibold uppercase tracking-label text-muted">Satisfacción promedio</p>
                    <p class="mt-2 font-serif text-3xl font-semibold text-brand-600 lg:mt-1 lg:text-2xl">{{ $reporte['satisfaccion'] ?? 'Sin datos' }}</p>
                </div>
            </div>

            <!-- Detalle por Secciones -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-4 xl:grid-cols-4">
                <div class="rounded-2xl border border-line bg-white p-6 shadow-soft lg:p-5">
                    <h3 class="mb-4 text-headline font-semibold text-ink lg:mb-3">Pacientes</h3>
                    <div class="space-y-3 rounded-xl border border-line/80 bg-cream/50 p-4 text-sm">
                        <p class="text-ink-soft">Total histórico registrado: <span class="font-semibold text-ink">{{ $reporte['pacientes']['total'] }}</span></p>
                        <p class="text-ink-soft">Nuevos en el período: <span class="font-semibold text-ink">{{ $reporte['pacientes']['nuevos'] }}</span></p>
                    </div>
                </div>

                <div class="rounded-2xl border border-line bg-white p-6 shadow-soft lg:p-5">
                    <h3 class="mb-4 text-headline font-semibold text-ink lg:mb-3">
                        Citas por estado ({{ $reporte['citas']['total'] }} en el período)
                    </h3>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-line/60">
                            @foreach ($reporte['citas']['por_estado'] as $estado => $total)
                                <tr class="transition hover:bg-cream/60">
                                    <td class="py-2.5 text-ink-soft lg:py-1.5">{{ $estadoLabel[$estado] ?? $estado }}</td>
                                    <td class="py-2.5 text-right font-semibold text-ink lg:py-1.5">{{ $total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="rounded-2xl border border-line bg-white p-6 shadow-soft lg:p-5">
                    <h3 class="mb-4 text-headline font-semibold text-ink lg:mb-3">Tratamientos más solicitados</h3>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-line/60">
                            @forelse ($reporte['catalogo']['tratamientos_mas_solicitados'] as $fila)
                                <tr class="transition hover:bg-cream/60">
                                    <td class="py-2.5 text-ink-soft lg:py-1.5">{{ $fila['nombre'] }}</td>
                                    <td class="py-2.5 text-right font-semibold text-ink lg:py-1.5">{{ $fila['total'] }}</td>
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

                <div class="rounded-2xl border border-line bg-white p-6 shadow-soft lg:p-5">
                    <h3 class="mb-4 text-headline font-semibold text-ink lg:mb-3">
                        Historial clínico ({{ $reporte['historial']['total'] }} {{ $reporte['historial']['total'] === 1 ? 'consulta' : 'consultas' }})
                    </h3>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-line/60">
                            @foreach ($reporte['historial']['por_tipo'] as $tipo => $total)
                                <tr class="transition hover:bg-cream/60">
                                    <td class="py-2.5 text-ink-soft lg:py-1.5">{{ $tipoConsultaLabel[$tipo] ?? $tipo }}</td>
                                    <td class="py-2.5 text-right font-semibold text-ink lg:py-1.5">{{ $total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

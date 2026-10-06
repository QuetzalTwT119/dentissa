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
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Reportes administrativos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="GET" action="{{ route('admin.reportes.index') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="fecha_inicio" value="Desde" />
                        <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1" value="{{ old('fecha_inicio', $reporte['rango']['fecha_inicio']) }}" />
                    </div>
                    <div>
                        <x-input-label for="fecha_fin" value="Hasta" />
                        <x-text-input id="fecha_fin" name="fecha_fin" type="date" class="mt-1" value="{{ old('fecha_fin', $reporte['rango']['fecha_fin']) }}" />
                        <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
                    </div>

                    <x-primary-button>Filtrar</x-primary-button>

                    <div class="ms-auto flex gap-3">
                        <a href="{{ route('admin.reportes.pdf').$queryString }}">
                            <x-secondary-button type="button">Descargar PDF</x-secondary-button>
                        </a>
                        <a href="{{ route('admin.reportes.csv').$queryString }}">
                            <x-secondary-button type="button">Descargar Excel</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Tasa de ocupación</p>
                    <p class="mt-1 text-2xl font-semibold text-brand-primary">{{ $reporte['ocupacion']['tasa_ocupacion'] }}%</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Tasa de inasistencia</p>
                    <p class="mt-1 text-2xl font-semibold text-brand-primary">{{ $reporte['citas']['tasa_inasistencia'] }}%</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Eficiencia (citas/1.5h)</p>
                    <p class="mt-1 text-2xl font-semibold text-brand-primary">{{ $reporte['eficiencia']['eficiencia'] }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Comparativa Gerín</p>
                    <p class="mt-1 text-2xl font-semibold text-brand-primary">{{ $reporte['eficiencia']['comparativa_gerin'] }}×</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Satisfacción promedio</p>
                    <p class="mt-1 text-2xl font-semibold text-brand-primary">{{ $reporte['satisfaccion'] ?? 'Sin datos' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-3 text-sm font-medium text-gray-900">Pacientes</h3>
                    <p class="text-sm text-gray-600">Total registrados: <span class="font-semibold">{{ $reporte['pacientes']['total'] }}</span></p>
                    <p class="text-sm text-gray-600">Nuevos en el período: <span class="font-semibold">{{ $reporte['pacientes']['nuevos'] }}</span></p>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-3 text-sm font-medium text-gray-900">
                        Citas por estado ({{ $reporte['citas']['total'] }} en el período)
                    </h3>
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach ($reporte['citas']['por_estado'] as $estado => $total)
                                <tr>
                                    <td class="py-1 text-gray-600">{{ $estadoLabel[$estado] ?? $estado }}</td>
                                    <td class="py-1 text-right font-medium">{{ $total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-3 text-sm font-medium text-gray-900">Tratamientos más solicitados</h3>
                    <table class="w-full text-sm">
                        <tbody>
                            @forelse ($reporte['catalogo']['tratamientos_mas_solicitados'] as $fila)
                                <tr>
                                    <td class="py-1 text-gray-600">{{ $fila['nombre'] }}</td>
                                    <td class="py-1 text-right font-medium">{{ $fila['total'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-1 text-gray-500">Sin citas en el período.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <p class="mt-3 text-xs text-gray-500">
                        {{ $reporte['catalogo']['servicios_activos'] }} servicios activos · {{ $reporte['catalogo']['tratamientos_activos'] }} tratamientos activos
                    </p>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="mb-3 text-sm font-medium text-gray-900">
                        Historial clínico ({{ $reporte['historial']['total'] }} consultas)
                    </h3>
                    <table class="w-full text-sm">
                        <tbody>
                            @foreach ($reporte['historial']['por_tipo'] as $tipo => $total)
                                <tr>
                                    <td class="py-1 text-gray-600">{{ $tipoConsultaLabel[$tipo] ?? $tipo }}</td>
                                    <td class="py-1 text-right font-medium">{{ $total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

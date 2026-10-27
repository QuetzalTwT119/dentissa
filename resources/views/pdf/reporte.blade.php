<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Reporte administrativo</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { color: #d75078; font-size: 18px; margin-bottom: 0; }
        .subtitulo { color: #6b7280; margin-top: 4px; margin-bottom: 20px; }
        .seccion { margin-bottom: 18px; }
        .seccion h2 { font-size: 13px; color: #d75078; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px 6px; text-align: left; }
        .kpis td { width: 50%; }
        .kpi-valor { font-weight: bold; }
    </style>
</head>
<body>
    <h1>Dentissa · Reporte administrativo</h1>
    <p class="subtitulo">
        Período: {{ $reporte['rango']['fecha_inicio'] }} al {{ $reporte['rango']['fecha_fin'] }}
        &middot; Generado el {{ now()->format('d/m/Y H:i') }}
    </p>

    <div class="seccion">
        <h2>Indicadores clave</h2>
        <table class="kpis">
            <tr>
                <td>Tasa de ocupación: <span class="kpi-valor">{{ $reporte['ocupacion']['tasa_ocupacion'] }}%</span></td>
                <td>Tasa de inasistencia: <span class="kpi-valor">{{ $reporte['citas']['tasa_inasistencia'] }}%</span></td>
            </tr>
            <tr>
                <td>Eficiencia (citas por bloque 1.5h): <span class="kpi-valor">{{ $reporte['eficiencia']['eficiencia'] }}</span></td>
                <td>Citas completadas por hora: <span class="kpi-valor">{{ $reporte['eficiencia']['citas_por_hora'] }}</span></td>
            </tr>
            <tr>
                <td>Satisfacción promedio: <span class="kpi-valor">{{ $reporte['satisfaccion'] ?? 'Sin datos' }}</span></td>
                <td></td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h2>Pacientes</h2>
        <table>
            <tr><td>Total histórico registrado</td><td>{{ $reporte['pacientes']['total'] }}</td></tr>
            <tr><td>Nuevos en el período</td><td>{{ $reporte['pacientes']['nuevos'] }}</td></tr>
        </table>
    </div>

    <div class="seccion">
        <h2>Citas por estado</h2>
        <table>
            @foreach ($reporte['citas']['por_estado'] as $estado => $total)
                <tr><td>{{ ucfirst(str_replace('_', ' ', $estado)) }}</td><td>{{ $total }}</td></tr>
            @endforeach
            <tr><td><strong>Total en el período</strong></td><td><strong>{{ $reporte['citas']['total'] }}</strong></td></tr>
        </table>
    </div>

    <div class="seccion">
        <h2>Tratamientos más solicitados</h2>
        <table>
            @forelse ($reporte['catalogo']['tratamientos_mas_solicitados'] as $fila)
                <tr><td>{{ $fila['nombre'] }}</td><td>{{ $fila['total'] }}</td></tr>
            @empty
                <tr><td colspan="2">Sin citas registradas en el período.</td></tr>
            @endforelse
            <tr><td>Servicios activos en el catálogo actual</td><td>{{ $reporte['catalogo']['servicios_activos'] }}</td></tr>
            <tr><td>Tratamientos activos en el catálogo actual</td><td>{{ $reporte['catalogo']['tratamientos_activos'] }}</td></tr>
        </table>
    </div>

    <div class="seccion">
        <h2>Historial clínico por tipo de consulta</h2>
        <table>
            @foreach ($reporte['historial']['por_tipo'] as $tipo => $total)
                <tr><td>{{ ucfirst(str_replace('_', ' ', $tipo)) }}</td><td>{{ $total }}</td></tr>
            @endforeach
            <tr><td><strong>Total consultas</strong></td><td><strong>{{ $reporte['historial']['total'] }}</strong></td></tr>
        </table>
    </div>
</body>
</html>

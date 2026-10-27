<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @include('partials.favicon')
    <title>Historial clínico</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        h1 { color: #d75078; font-size: 18px; margin-bottom: 0; }
        .subtitulo { color: #6b7280; margin-top: 4px; margin-bottom: 20px; }
        .consulta { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 14px; }
        .consulta h2 { font-size: 13px; color: #d75078; margin: 0 0 6px; }
        .campo { margin-bottom: 4px; }
        .campo strong { display: inline-block; width: 140px; }
        .vacio { color: #9ca3af; text-align: center; padding: 20px; }
    </style>
</head>
<body>
    <h1>Dentissa · Historial clínico</h1>
    <p class="subtitulo">
        Paciente: {{ $paciente->nombre }} {{ $paciente->apellido }}
        &middot; Generado el {{ now()->format('d/m/Y H:i') }}
    </p>

    @forelse ($consultas as $consulta)
        <div class="consulta">
            <h2>
                {{ \Illuminate\Support\Carbon::parse($consulta->fecha_consulta)->format('d/m/Y') }}
                — {{ ucfirst(str_replace('_', ' ', $consulta->tipo_consulta->value)) }}
            </h2>
            <div class="campo"><strong>Doctor:</strong> {{ $consulta->doctor->nombre ?? '—' }} {{ $consulta->doctor->apellido ?? '' }}</div>
            <div class="campo"><strong>Motivo:</strong> {{ $consulta->motivo_consulta }}</div>
            @if ($consulta->diagnostico)
                <div class="campo"><strong>Diagnóstico:</strong> {{ $consulta->diagnostico }}</div>
            @endif
            @if ($consulta->tratamiento_realizado)
                <div class="campo"><strong>Tratamiento realizado:</strong> {{ $consulta->tratamiento_realizado }}</div>
            @endif
            @if ($consulta->medicamentos_recetados)
                <div class="campo"><strong>Medicamentos:</strong> {{ $consulta->medicamentos_recetados }}</div>
            @endif
            @if ($consulta->observaciones)
                <div class="campo"><strong>Observaciones:</strong> {{ $consulta->observaciones }}</div>
            @endif
        </div>
    @empty
        <p class="vacio">Este paciente todavía no tiene consultas registradas.</p>
    @endforelse
</body>
</html>

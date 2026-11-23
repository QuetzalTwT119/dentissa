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
        .historia { border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 18px; }
        .historia h2 { font-size: 13px; color: #d75078; margin: 0 0 6px; }
        .riesgo { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; border-radius: 6px; padding: 8px 10px; margin-bottom: 8px; }
    </style>
</head>
<body>
    <h1>Dentissa · Historial clínico</h1>
    <p class="subtitulo">
        Paciente: {{ $paciente->nombre }} {{ $paciente->apellido }}
        &middot; Generado el {{ now()->format('d/m/Y H:i') }}
    </p>

    {{-- Historia clínica (cuestionario, 22/11/2026). --}}
    @if ($historia = $paciente->historiaClinica)
        @php $HC = \App\Models\HistoriaClinica::class; $lista = fn ($claves, $cat) => implode(', ', $HC::nombres($claves, $cat)) ?: 'Ninguno'; @endphp
        <div class="historia">
            <h2>Historia clínica</h2>
            @if ($historia->alertas())
                <div class="riesgo"><strong>Antes de atender:</strong> {{ implode(' · ', $historia->alertas()) }}</div>
            @endif
            <div class="campo"><strong>Edad / sexo:</strong> {{ $historia->edad() !== null ? $historia->edad().' años' : '—' }} · {{ $HC::SEXOS[$historia->sexo] ?? '—' }}</div>
            <div class="campo"><strong>Ocupación:</strong> {{ $historia->ocupacion ?: '—' }}</div>
            <div class="campo"><strong>Tipo de sangre:</strong> {{ $historia->tipo_sangre ?: '—' }}</div>
            <div class="campo"><strong>Ant. familiares:</strong> {{ $lista($historia->antecedentes_familiares, $HC::ANTECEDENTES_FAMILIARES) }} {{ $historia->antecedentes_familiares_notas }}</div>
            <div class="campo"><strong>Ant. personales:</strong> {{ $lista($historia->antecedentes_personales, $HC::ANTECEDENTES_PERSONALES) }} {{ $historia->antecedentes_personales_notas }}</div>
            <div class="campo"><strong>Alergias:</strong> {{ $lista($historia->alergias, $HC::ALERGIAS) }} {{ $historia->alergias_otras }}</div>
            <div class="campo"><strong>Medicamentos:</strong> {{ $historia->medicamentos_actuales ?: 'Ninguno' }}</div>
            @if ($historia->sexo === 'femenino')
                <div class="campo"><strong>Embarazo / lactancia:</strong> {{ $historia->embarazo ? 'Sí' : 'No' }} / {{ $historia->lactancia ? 'Sí' : 'No' }}</div>
            @endif
            <div class="campo"><strong>Ant. dentales:</strong> {{ $lista($historia->antecedentes_dentales, $HC::ANTECEDENTES_DENTALES) }} {{ $historia->ultima_visita_dentista ? '· Última visita: '.$historia->ultima_visita_dentista : '' }} {{ $historia->antecedentes_dentales_notas }}</div>
            <div class="campo"><strong>Hábitos:</strong> {{ $lista($historia->habitos, $HC::HABITOS) }} {{ $historia->habitos_notas }}</div>
            @if (filled($historia->observaciones))
                <div class="campo"><strong>Observaciones:</strong> {{ $historia->observaciones }}</div>
            @endif
        </div>
    @endif

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

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
            <div class="campo"><strong>Ocupación / estado civil:</strong> {{ $historia->ocupacion ?: '—' }} · {{ $HC::ESTADOS_CIVILES[$historia->estado_civil] ?? '—' }}</div>
            <div class="campo"><strong>Contacto de emergencia:</strong> {{ collect([$historia->contacto_emergencia_nombre, $historia->contacto_emergencia_parentesco, $historia->contacto_emergencia_telefono])->filter()->implode(' · ') ?: '—' }}</div>
            <div class="campo"><strong>Médico de cabecera:</strong> {{ collect([$historia->medico_cabecera_nombre, $historia->medico_cabecera_telefono])->filter()->implode(' · ') ?: '—' }}</div>
            <div class="campo"><strong>Tipo de sangre:</strong> {{ $historia->tipo_sangre ?: '—' }}</div>
            <div class="campo"><strong>Ant. familiares:</strong> {{ $lista($historia->antecedentes_familiares, $HC::ANTECEDENTES_FAMILIARES) }} {{ $historia->antecedentes_familiares_notas }}</div>
            <div class="campo"><strong>Ant. personales:</strong> {{ $lista($historia->antecedentes_personales, $HC::ANTECEDENTES_PERSONALES) }} {{ $historia->antecedentes_personales_notas }}</div>
            <div class="campo"><strong>Alergias:</strong> {{ $lista($historia->alergias, $HC::ALERGIAS) }} {{ $historia->alergias_otras }}</div>
            <div class="campo"><strong>Medicamentos:</strong> {{ $historia->medicamentos_actuales ?: 'Ninguno' }}</div>
            @if (filled($historia->tratamiento_medico_actual))
                <div class="campo"><strong>Tratamiento médico actual:</strong> {{ $historia->tratamiento_medico_actual }}</div>
            @endif
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

    {{-- Última exploración clínica con odontograma (23/11/2026). --}}
    @if ($exploracion ?? null)
        @php
            $EX = \App\Models\ExploracionClinica::class;
            $estructuras = collect($exploracion->estomatologico ?? [])->map(fn ($e, $k) => ($EX::ESTRUCTURAS[$k] ?? $k).': '.($e['hallazgo'] ?: 'normal'))->implode(' · ');
            $encias = collect($exploracion->periodontal ?? [])->map(fn ($d, $k) => ($EX::PERIODONTAL[$k] ?? $k).' ('.$d.')')->implode(' · ');
            $dientesPorEstado = collect($exploracion->odontograma ?? [])->groupBy(fn ($e) => $e, preserveKeys: true)
                ->map(fn ($grupo, $e) => ($EX::ESTADOS_DIENTE[$e][0] ?? $e).': '.$grupo->keys()->implode(', '))->implode(' · ');
        @endphp
        <div class="historia">
            <h2>Exploración clínica · {{ $exploracion->fecha->format('d/m/Y') }}{{ $exploracion->doctor ? ' · '.$exploracion->doctor->nombre.' '.$exploracion->doctor->apellido : '' }}</h2>
            <div class="campo"><strong>Examen de la boca:</strong> {{ $estructuras ?: '—' }}</div>
            <div class="campo"><strong>ATM:</strong> {{ $exploracion->atm ?: '—' }}</div>
            <div class="campo"><strong>Encías:</strong> {{ $encias ?: 'Sin hallazgos' }}</div>
            <div class="campo"><strong>Higiene / O'Leary:</strong> {{ $EX::HIGIENE[$exploracion->higiene_oral] ?? '—' }} · {{ $exploracion->indiceOleary() !== null ? $exploracion->indiceOleary().' %' : '—' }}</div>
            @foreach ($exploracion->pulpares ?? [] as $fila)
                <div class="campo"><strong>Pulpa, diente {{ $fila['diente'] ?? '—' }}:</strong> {{ $fila['signos'] }} {{ filled($fila['diagnostico'] ?? null) ? '· '.$fila['diagnostico'] : '' }}</div>
            @endforeach
            @foreach ($exploracion->radiografica ?? [] as $fila)
                <div class="campo"><strong>Radiografía, diente {{ $fila['diente'] ?? '—' }}:</strong> {{ $fila['hallazgo'] }}</div>
            @endforeach
            <div class="campo"><strong>Odontograma:</strong> {{ $dientesPorEstado ?: 'Sin marcar' }}</div>
            @if (filled($exploracion->otros_hallazgos))
                <div class="campo"><strong>Otros hallazgos:</strong> {{ $exploracion->otros_hallazgos }}</div>
            @endif
            @if (filled($exploracion->diagnosticos))
                <div class="campo"><strong>Diagnósticos:</strong> {{ $exploracion->diagnosticos }}</div>
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
                <div class="campo"><strong>Tratamiento realizado:</strong> {{ $consulta->tratamiento_realizado }}{{ $consulta->dientes ? ' · Diente(s): '.$consulta->dientes : '' }}</div>
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

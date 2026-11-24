{{--
    Carta de consentimiento informado (24/11/2026), con los elementos de la
    NOM-004-SSA3-2012: establecimiento, título, lugar y fecha, acto autorizado,
    riesgos y beneficios, autorización para contingencias, y nombre y firma del
    paciente o representante, del odontólogo y de dos testigos.
--}}
@php
    $doctor = $consentimiento->doctor;
    $fecha = $consentimiento->created_at->copy()->locale('es')->translatedFormat('j \d\e F \d\e Y');
    $nombreDoctor = $doctor ? $doctor->nombre.' '.$doctor->apellido : '________________________';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Consentimiento informado</title>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: sans-serif; font-size: 11.5px; color: #1f2937; line-height: 1.45; }
        .marca { color: #d75078; font-size: 18px; font-style: italic; font-weight: bold; margin: 0; }
        .sub { color: #6b7280; font-size: 9.5px; letter-spacing: 2px; text-transform: uppercase; margin: 2px 0 0; }
        .encabezado { border-bottom: 2px solid #d75078; padding-bottom: 8px; margin-bottom: 12px; }
        h1 { font-size: 15px; text-align: center; margin: 4px 0 10px; letter-spacing: 1px; }
        .datos { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .datos td { padding: 3px 0; }
        .etiqueta { color: #6b7280; width: 105px; }
        .bloque { margin-bottom: 8px; }
        .bloque strong { color: #d75078; }
        .alergias { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; border-radius: 6px; padding: 6px 9px; margin-bottom: 8px; }
        .firmas { width: 100%; margin-top: 34px; border-collapse: collapse; }
        .firmas td { width: 50%; padding: 30px 14px 0; text-align: center; vertical-align: bottom; }
        .linea { border-top: 1px solid #1f2937; padding-top: 4px; font-size: 10.5px; }
        .pie { margin-top: 18px; color: #6b7280; font-size: 9.5px; text-align: center; }
    </style>
</head>
<body>
    <div class="encabezado">
        <p class="marca">Dra. Melissa López</p>
        <p class="sub">Odontología Integral</p>
        @if (config('dentissa.contacto.direccion'))
            <p style="margin: 4px 0 0; font-size: 10px; color: #6b7280;">{{ config('dentissa.contacto.direccion') }}</p>
        @endif
    </div>

    <h1>CARTA DE CONSENTIMIENTO INFORMADO</h1>

    <table class="datos">
        <tr><td class="etiqueta">Paciente</td><td><strong>{{ $paciente->nombre_completo }}</strong>{{ $edad !== null ? ' · '.$edad.' años' : '' }}</td></tr>
        <tr><td class="etiqueta">Fecha</td><td>{{ $fecha }}</td></tr>
        <tr><td class="etiqueta">Procedimiento</td><td><strong>{{ $consentimiento->procedimiento }}</strong>{{ $consentimiento->dientes ? ' · Dientes: '.$consentimiento->dientes : '' }}</td></tr>
        <tr><td class="etiqueta">Odontólogo(a)</td><td>{{ $nombreDoctor }}{{ $doctor?->cedula_profesional ? ' · Céd. prof. '.$doctor->cedula_profesional : '' }}</td></tr>
    </table>

    @if ($alergias)
        <div class="alergias"><strong>Alergias registradas:</strong> {{ $alergias }}</div>
    @endif

    <p class="bloque">
        Yo, ______________________________________ (paciente o representante legal), declaro que
        {{ $nombreDoctor }} me explicó de forma clara y en un lenguaje que entiendo en qué consiste el
        procedimiento indicado, por qué se recomienda, lo que se espera lograr, sus riesgos y las alternativas.
    </p>
    <p class="bloque"><strong>Beneficios:</strong> {{ $consentimiento->beneficios }}</p>
    <p class="bloque"><strong>Riesgos y molestias posibles:</strong> {{ $consentimiento->riesgos }}</p>
    <p class="bloque"><strong>Alternativas:</strong> {{ $consentimiento->alternativas }}</p>
    <p class="bloque">
        Tuve la oportunidad de hacer preguntas y fueron respondidas. Informé mis enfermedades, alergias y los
        medicamentos que tomo. Autorizo que se realice el procedimiento y que se atiendan las contingencias o
        urgencias que se deriven de él, conforme a la libertad prescriptiva del personal de salud. Sé que puedo
        retirar este consentimiento en cualquier momento antes del procedimiento.
    </p>

    <table class="firmas">
        <tr>
            <td><div class="linea">Nombre y firma del paciente o representante legal</div></td>
            <td><div class="linea">{{ $nombreDoctor }}{{ $doctor?->cedula_profesional ? ' · Céd. '.$doctor->cedula_profesional : '' }}<br>Firma del odontólogo(a)</div></td>
        </tr>
        <tr>
            <td><div class="linea">Testigo 1: nombre y firma</div></td>
            <td><div class="linea">Testigo 2: nombre y firma</div></td>
        </tr>
    </table>

    <p class="pie">Folio C-{{ str_pad($consentimiento->id, 5, '0', STR_PAD_LEFT) }} · Conforme a la NOM-004-SSA3-2012 del expediente clínico.</p>
</body>
</html>

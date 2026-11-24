{{--
    Receta médica (24/11/2026). Datos que pide la normativa mexicana: nombre,
    cédula profesional y domicilio de quien la emite, fecha, nombre y edad del
    paciente, medicamento con indicaciones y firma. Las alergias se repiten para
    que la farmacia y el paciente las tengan presentes.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Receta</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
        .encabezado { border-bottom: 2px solid #d75078; padding-bottom: 10px; margin-bottom: 14px; }
        .marca { color: #d75078; font-size: 20px; font-style: italic; font-weight: bold; margin: 0; }
        .sub { color: #6b7280; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; margin: 2px 0 0; }
        .doctor { margin-top: 8px; font-size: 12px; }
        .doctor strong { font-size: 13px; }
        .datos { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .datos td { padding: 4px 0; vertical-align: top; }
        .datos .etiqueta { color: #6b7280; width: 110px; }
        .alergias { background: #fff1f2; border: 1px solid #fecdd3; color: #9f1239; border-radius: 6px; padding: 8px 10px; margin-bottom: 12px; }
        .rx { font-size: 26px; color: #d75078; font-weight: bold; margin: 6px 0 4px; }
        .medicamentos { white-space: pre-line; font-size: 13px; line-height: 1.55; min-height: 220px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; }
        .firma { margin-top: 70px; width: 260px; margin-left: auto; text-align: center; border-top: 1px solid #1f2937; padding-top: 6px; font-size: 11px; }
        .pie { position: fixed; bottom: -8px; left: 0; right: 0; text-align: center; color: #6b7280; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="encabezado">
        <p class="marca">Dra. Melissa López</p>
        <p class="sub">Odontología Integral</p>
        <p class="doctor">
            <strong>{{ $doctor->nombre }} {{ $doctor->apellido }}</strong>
            @if ($doctor->especialidad) · {{ $doctor->especialidad }} @endif
            <br>Cédula profesional: {{ $doctor->cedula_profesional }}
        </p>
    </div>

    <table class="datos">
        <tr>
            <td class="etiqueta">Paciente</td>
            <td><strong>{{ $paciente->nombre_completo }}</strong></td>
            <td class="etiqueta" style="width: 60px;">Fecha</td>
            <td style="width: 150px;">{{ $consulta->fecha_consulta->copy()->locale('es')->translatedFormat('j \d\e F \d\e Y') }}</td>
        </tr>
        <tr>
            <td class="etiqueta">Edad</td>
            <td>{{ $edad !== null ? $edad.' años' : '—' }}</td>
            <td class="etiqueta">Folio</td>
            <td>R-{{ str_pad($consulta->id, 5, '0', STR_PAD_LEFT) }}</td>
        </tr>
        @if ($consulta->diagnostico)
            <tr>
                <td class="etiqueta">Diagnóstico</td>
                <td colspan="3">{{ $consulta->diagnostico }}</td>
            </tr>
        @endif
    </table>

    @if ($alergias)
        <div class="alergias"><strong>Alergias:</strong> {{ $alergias }}</div>
    @endif

    <div class="rx">Rx</div>
    <div class="medicamentos">{{ $consulta->medicamentos_recetados }}</div>

    <div class="firma">
        {{ $doctor->nombre }} {{ $doctor->apellido }}<br>
        Céd. prof. {{ $doctor->cedula_profesional }}
    </div>

    <div class="pie">
        {{ collect([config('dentissa.contacto.direccion'), config('dentissa.contacto.telefono') ? 'Tel. '.config('dentissa.contacto.telefono') : null])->filter()->implode(' · ') }}
    </div>
</body>
</html>

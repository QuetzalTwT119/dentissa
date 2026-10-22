<?php

namespace App\Http\Controllers;

use App\Services\AsistenteAgendaService;
use App\Services\AsistenteIaLocalService;
use App\Services\AsistentePacienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsistentePacienteController extends Controller
{
    public function responder(Request $request, AsistentePacienteService $asistente, AsistenteIaLocalService $asistenteIa, AsistenteAgendaService $agenda): JsonResponse
    {
        $validated = $request->validate([
            'consulta' => ['required', 'string', 'min:3', 'max:300'],
            'conversacion_id' => ['required', 'uuid'],
        ]);

        // El widget conserva su flujo de consulta de horarios sin mezclarse
        // con la captura de datos y confirmación de reservas de Telegram.
        $conversacion = 'web:'.$validated['conversacion_id'];
        $agendaEnCurso = $agenda->tieneConsultaEnCurso($conversacion);
        $consulta = $asistente->responder($validated['consulta']);
        $coincidenciaSegura = $consulta['encontrada'] || ($consulta['ambigua'] ?? false);
        $respuestaAgenda = ($agendaEnCurso || ! $coincidenciaSegura)
            ? $agenda->responder($conversacion, $validated['consulta'])
            : null;
        $respuestaIa = (! $respuestaAgenda && ! $agendaEnCurso && ! $coincidenciaSegura)
            ? $asistenteIa->responder($validated['consulta'])
            : null;
        $respuestaFaq = (! $agendaEnCurso && $consulta['encontrada'])
            ? $consulta['respuesta']
            : null;

        $respuesta = $respuestaAgenda ?? $respuestaFaq ?? $respuestaIa['respuesta'] ?? $consulta['respuesta'];

        return response()->json([
            'encontrada' => $respuestaFaq !== null || (bool) $respuestaIa,
            'respuesta' => $respuesta,
            'pregunta' => $respuestaFaq !== null ? $consulta['pregunta'] : ($respuestaIa['pregunta'] ?? null),
            'accion' => null,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\AgendaTelegramService;
use App\Services\AsistenteIaLocalService;
use App\Services\AsistentePacienteService;
use App\Services\AtencionOportunaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsistentePacienteController extends Controller
{
    public function responder(Request $request, AsistentePacienteService $asistente, AsistenteIaLocalService $asistenteIa, AgendaTelegramService $agenda, AtencionOportunaService $atencion): JsonResponse
    {
        $validated = $request->validate([
            'consulta' => ['required', 'string', 'min:3', 'max:300'],
            'conversacion_id' => ['required', 'uuid'],
        ]);

        // La agenda usa el mismo flujo que Telegram, pero con una conversación
        // separada por sesión web. Así no se mezclan pacientes ni canales.
        $conversacion = 'web:'.$validated['conversacion_id'];
        $agendaEnCurso = $agenda->tieneConsultaEnCurso($conversacion);
        $consulta = $asistente->responder($validated['consulta']);
        $orientacion = $agendaEnCurso ? null : $atencion->responder($validated['consulta']);
        $coincidenciaSegura = $consulta['encontrada'] || ($consulta['ambigua'] ?? false);
        $respuestaAgenda = (! $orientacion && ($agendaEnCurso || ! $coincidenciaSegura))
            ? $agenda->responder($conversacion, $validated['consulta'])
            : null;
        $respuestaIa = (! $orientacion && ! $respuestaAgenda && ! $agendaEnCurso && ! $coincidenciaSegura)
            ? $asistenteIa->responder($validated['consulta'])
            : null;
        $respuestaFaq = (! $orientacion && ! $agendaEnCurso && $consulta['encontrada'])
            ? $consulta['respuesta']
            : null;

        $respuesta = $orientacion['respuesta']
            ?? ($respuestaAgenda ?? $respuestaFaq ?? $respuestaIa['respuesta'] ?? $consulta['respuesta']);

        return response()->json([
            'encontrada' => $respuestaFaq !== null || (bool) $respuestaIa,
            'respuesta' => $respuesta,
            'pregunta' => $respuestaFaq !== null ? $consulta['pregunta'] : ($respuestaIa['pregunta'] ?? null),
            'accion' => $orientacion['accion'] ?? null,
        ]);
    }
}

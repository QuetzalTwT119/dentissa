<?php

namespace App\Http\Controllers;

use App\Services\AsistentePacienteService;
use App\Services\AgendaTelegramService;
use App\Services\AtencionOportunaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsistentePacienteController extends Controller
{
    public function responder(Request $request, AsistentePacienteService $asistente, AgendaTelegramService $agenda, AtencionOportunaService $atencion): JsonResponse
    {
        $validated = $request->validate([
            'consulta' => ['required', 'string', 'min:3', 'max:300'],
            'conversacion_id' => ['required', 'uuid'],
        ]);

        // La agenda usa el mismo flujo que Telegram, pero con una conversación
        // separada por sesión web. Así no se mezclan pacientes ni canales.
        $conversacion = 'web:'.$validated['conversacion_id'];
        $consulta = $asistente->responder($validated['consulta']);
        $orientacion = $agenda->tieneConsultaEnCurso($conversacion) ? null : $atencion->responder($validated['consulta']);

        $respuesta = $orientacion['respuesta'] ?? ((! $agenda->tieneConsultaEnCurso($conversacion) && $consulta['encontrada'])
            ? $consulta['respuesta']
            : ($agenda->responder($conversacion, $validated['consulta']) ?? $consulta['respuesta']));

        return response()->json([
            'encontrada' => $consulta['encontrada'],
            'respuesta' => $respuesta,
            'pregunta' => $consulta['encontrada'] ? $consulta['pregunta'] : null,
            'accion' => $orientacion['accion'] ?? null,
        ]);
    }
}

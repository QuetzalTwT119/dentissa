<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rutas del ESP32 (26/11/2026). Exigen el encabezado «X-Dispositivo-Clave» con
 * la clave de DENTISSA_IOT_CLAVE. Sin clave configurada quedan cerradas (falla
 * cerrado): nunca se abren por olvido.
 */
class VerificarDispositivoIot
{
    public function handle(Request $request, Closure $next): Response
    {
        $clave = (string) config('dentissa.iot.clave_dispositivo');

        if ($clave === '') {
            return response()->json(['ok' => false, 'mensaje' => 'El IoT no está configurado.'], 503);
        }

        if (! hash_equals($clave, (string) $request->header('X-Dispositivo-Clave'))) {
            return response()->json(['ok' => false, 'mensaje' => 'Dispositivo no autorizado.'], 401);
        }

        return $next($request);
    }
}

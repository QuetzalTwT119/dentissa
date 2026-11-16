<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en todas las respuestas web.
 *
 * - X-Frame-Options: otra página no puede mostrar Dentissa en un marco para
 *   engañar al personal (clickjacking).
 * - X-Content-Type-Options: el navegador no adivina tipos de archivo.
 * - Referrer-Policy: los enlaces firmados de los recordatorios no se filtran
 *   completos a otros sitios.
 * - Strict-Transport-Security: solo en HTTPS, para que el navegador no vuelva
 *   a usar HTTP. En local (HTTP) no se envía.
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respuesta;
    }
}

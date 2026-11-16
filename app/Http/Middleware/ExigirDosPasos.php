<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un administrador sin verificación en dos pasos solo puede activarla o
 * cerrar sesión: el resto del panel (historiales incluidos) queda bloqueado
 * hasta que la configure. Las páginas públicas no se ven afectadas.
 */
class ExigirDosPasos
{
    private const PERMITIDAS = ['dos-pasos.show', 'dos-pasos.confirmar', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        $ruta = $request->route();

        if (
            $usuario
            && $ruta
            && in_array('auth', $ruta->gatherMiddleware(), true)
            && ! in_array($ruta->getName(), self::PERMITIDAS, true)
            && $usuario->debeUsarDosPasos()
            && ! $usuario->tieneDosPasos()
        ) {
            if ($request->expectsJson()) {
                abort(403, 'Activa la verificación en dos pasos para continuar.');
            }

            return redirect()->route('dos-pasos.show')
                ->with('status', 'Antes de continuar, activa la verificación en dos pasos. Es obligatoria para el administrador.');
        }

        return $next($request);
    }
}

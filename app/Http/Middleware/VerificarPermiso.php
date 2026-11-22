<?php

namespace App\Http\Middleware;

use App\Support\Permisos;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permiso por módulo (22/11/2026). Busca el módulo de la ruta actual en
 * config/permisos.php y deja pasar solo si el rol del usuario lo tiene.
 * Una ruta sin módulo se niega (falla cerrado). Sustituye a "role:admin".
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        $modulo = Permisos::moduloDeRuta($request->route()?->getName());

        if (! $usuario || $modulo === null || ! $usuario->puede($modulo)) {
            abort(403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rutas de la app móvil (26/11/2026). Exigen «Authorization: Bearer <token>»,
 * el que entrega POST /api/app/login. Se compara la huella SHA-256 guardada;
 * la cuenta debe estar activa y el token sin vencer.
 */
class VerificarTokenApp
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();
        $usuario = $token === '' ? null : User::query()
            ->where('app_token', hash('sha256', $token))
            ->where('app_token_expira_en', '>', now())
            ->first();

        if (! $usuario || ! $usuario->activo) {
            return response()->json(['ok' => false, 'mensaje' => 'Inicia sesión de nuevo en la app.'], 401);
        }

        // Para el resto de la petición, quién la hizo (bitácora, permisos).
        Auth::setUser($usuario);

        return $next($request);
    }
}

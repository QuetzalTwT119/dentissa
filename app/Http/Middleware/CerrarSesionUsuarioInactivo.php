<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * `activo` antes solo se revisaba al iniciar sesión: una cuenta desactivada
 * seguía dentro hasta que caducara su sesión, o semanas con "Recordarme".
 * Ahora se revisa en cada petición y la sesión se cierra al instante.
 */
class CerrarSesionUsuarioInactivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->activo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(401);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Tu cuenta fue desactivada. Si crees que es un error, habla con el administrador.']);
        }

        return $next($request);
    }
}

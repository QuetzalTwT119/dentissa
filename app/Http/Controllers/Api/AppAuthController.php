<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Inicio de sesión de la app móvil del personal (26/11/2026: ahora entrega un
 * token; antes solo devolvía los datos y las demás rutas no sabían quién
 * pedía). El límite de intentos está en la ruta (throttle:app-login).
 */
class AppAuthController extends Controller
{
    /** Días que dura el token antes de pedir el inicio de sesión otra vez. */
    public const DIAS_TOKEN = 30;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Correo o contraseña incorrectos.',
            ], 401);
        }

        if (! $user->activo) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Tu cuenta está desactivada. Contacta a la administradora.',
            ], 403);
        }

        // Solo el personal (todos los roles de RolUsuario lo son).
        $rol = $user->rol?->value;
        if (! in_array($rol, ['super_admin', 'doctor', 'asistente'], true)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Acceso exclusivo para personal de la clínica.',
            ], 403);
        }

        // El token se muestra una sola vez; en la base queda solo su huella.
        $token = Str::random(60);
        $expira = now()->addDays(self::DIAS_TOKEN);
        $user->forceFill([
            'app_token' => hash('sha256', $token),
            'app_token_expira_en' => $expira,
            'ultimo_acceso' => now(),
        ])->save();

        return response()->json([
            'ok' => true,
            'token' => $token,
            'expira_en' => $expira->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'email' => $user->email,
                'rol' => $rol,
                'foto_perfil' => $user->foto_perfil,
                'activo' => (int) $user->activo,
            ],
        ]);
    }

    /** Cierra la sesión de la app: el token deja de servir. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->forceFill(['app_token' => null, 'app_token_expira_en' => null])->save();

        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AppAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Correo o contraseña incorrectos.',
            ], 401);
        }

        if ((int) $user->activo !== 1) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Tu cuenta está desactivada. Contacta a la administradora.',
            ], 403);
        }

        $rolRaw = $user->rol instanceof \BackedEnum
            ? $user->rol->value
            : ($user->getRawOriginal('rol') ?? $user->rol);
        $rol = strtolower(trim((string) $rolRaw));

        if (!in_array($rol, ['super_admin', 'doctor', 'asistente'], true)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Acceso exclusivo para personal de la clínica.',
            ], 403);
        }

        User::where('id', $user->id)->update(['ultimo_acceso' => now()]);

        return response()->json([
            'ok' => true,
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
}
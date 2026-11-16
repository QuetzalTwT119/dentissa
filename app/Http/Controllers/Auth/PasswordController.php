<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $auditoria->registrarCambio(
            $request,
            'usuario.password_actualizada',
            $user,
            [],
            fn () => $user->update([
                'password' => Hash::make($validated['password']),
            ]),
        );

        $this->cerrarOtrasSesiones($request);

        return back()->with('status', 'password-updated');
    }

    /**
     * Si alguien más conocía la contraseña anterior, pierde el acceso: se
     * borran las demás sesiones guardadas y se invalida "Recordarme". La
     * sesión actual sigue abierta con un identificador nuevo.
     */
    private function cerrarOtrasSesiones(Request $request): void
    {
        $user = $request->user();
        $request->session()->regenerate();

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getAuthIdentifier())
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        $user->setRememberToken(Str::random(60));
        $user->saveQuietly();
    }
}

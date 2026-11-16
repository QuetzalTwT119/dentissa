<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DosPasosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Segundo paso del inicio de sesión: después de la contraseña se pide el
 * código de la app o un código de recuperación. El intento caduca en 5
 * minutos y el formulario tiene su propio límite de intentos.
 */
class RetoDosPasosController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->usuarioPendiente($request)) {
            return redirect()->route('login');
        }

        return view('auth.dos-pasos');
    }

    public function store(Request $request, DosPasosService $dosPasos): RedirectResponse
    {
        $usuario = $this->usuarioPendiente($request);
        if (! $usuario) {
            return redirect()->route('login')
                ->withErrors(['email' => 'El tiempo para escribir el código terminó. Inicia sesión de nuevo.']);
        }

        $request->validate(['codigo' => ['required', 'string', 'max:20']]);

        if (! $dosPasos->verificar($usuario, $request->string('codigo')->toString())) {
            throw ValidationException::withMessages([
                'codigo' => 'El código no es correcto o ya se usó. Escribe el código actual de tu app o un código de recuperación.',
            ]);
        }

        $recordar = (bool) $request->session()->get('dos_pasos.recordar');
        $request->session()->forget('dos_pasos');
        Auth::guard('web')->login($usuario, $recordar);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function usuarioPendiente(Request $request): ?User
    {
        $pendiente = $request->session()->get('dos_pasos');

        if (! is_array($pendiente) || ($pendiente['expira'] ?? 0) < now()->timestamp) {
            $request->session()->forget('dos_pasos');

            return null;
        }

        $usuario = User::find($pendiente['usuario_id'] ?? null);

        return $usuario && $usuario->activo && $usuario->tieneDosPasos() ? $usuario : null;
    }
}

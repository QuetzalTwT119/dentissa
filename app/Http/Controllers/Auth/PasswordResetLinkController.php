<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink($request->only('email'));

        // La respuesta es igual exista o no la cuenta, para no revelar
        // qué correos están registrados en el sistema.
        return back()->with(
            'status',
            'Si el correo está asociado a una cuenta, recibirás un enlace para restablecer la contraseña. Revisa también la carpeta de correo no deseado; si ya solicitaste uno hace poco, espera unos minutos antes de intentarlo de nuevo.'
        );
    }
}

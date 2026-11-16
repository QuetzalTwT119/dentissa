<?php

namespace App\Http\Controllers;

use App\Services\DosPasosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Activar, revisar y desactivar la verificación en dos pasos desde el perfil.
 */
class DosPasosController extends Controller
{
    public function show(Request $request, DosPasosService $dosPasos): View
    {
        $usuario = $request->user();
        $qr = null;

        if (! $usuario->tieneDosPasos()) {
            // El secreto vive en la sesión hasta que el usuario confirme un
            // código: así un intento abandonado no deja nada a medias.
            $secreto = $request->session()->get('dos_pasos_pendiente') ?? $dosPasos->nuevoSecreto();
            $request->session()->put('dos_pasos_pendiente', $secreto);
            $qr = ['svg' => $dosPasos->qrSvg($usuario, $secreto), 'clave' => trim(chunk_split($secreto, 4, ' '))];
        }

        return view('profile.dos-pasos', [
            'usuario' => $usuario,
            'qr' => $qr,
            'codigos' => $request->session()->get('dos_pasos_codigos'),
            'restantes' => $dosPasos->codigosRestantes($usuario),
            'obligatoria' => $usuario->debeUsarDosPasos(),
        ]);
    }

    public function confirmar(Request $request, DosPasosService $dosPasos): RedirectResponse
    {
        $request->validate(['codigo' => ['required', 'string', 'max:10']]);
        $secreto = $request->session()->get('dos_pasos_pendiente');
        $paso = $secreto ? $dosPasos->verificarPendiente($secreto, $request->string('codigo')->toString()) : null;

        if ($paso === null) {
            throw ValidationException::withMessages(['codigo' => 'El código no coincide. Revisa que la hora de tu celular sea automática y escribe el código actual.']);
        }

        $codigos = $dosPasos->activar($request->user(), $secreto, $paso);
        $request->session()->forget('dos_pasos_pendiente');

        return redirect()->route('dos-pasos.show')
            ->with('dos_pasos_codigos', $codigos)
            ->with('status', 'Verificación en dos pasos activada.');
    }

    public function regenerar(Request $request, DosPasosService $dosPasos): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        return redirect()->route('dos-pasos.show')
            ->with('dos_pasos_codigos', $dosPasos->regenerarCodigos($request->user()))
            ->with('status', 'Generamos códigos de recuperación nuevos; los anteriores ya no sirven.');
    }

    public function desactivar(Request $request, DosPasosService $dosPasos): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        if ($request->user()->debeUsarDosPasos()) {
            throw ValidationException::withMessages(['current_password' => 'Para el administrador la verificación en dos pasos es obligatoria.']);
        }

        $dosPasos->desactivar($request->user());

        return redirect()->route('dos-pasos.show')->with('status', 'Verificación en dos pasos desactivada.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MensajeContacto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MensajeContactoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        MensajeContacto::create($validated);

        return redirect('/#contacto')
            ->with('success', 'Gracias por escribirnos, te responderemos pronto.');
    }
}

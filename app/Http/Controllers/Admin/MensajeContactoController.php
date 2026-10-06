<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MensajeContacto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MensajeContactoController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'leido' => ['nullable', 'in:0,1'],
        ]);

        $mensajes = MensajeContacto::when(
            $request->filled('leido'),
            fn ($q) => $q->where('leido', $validated['leido'])
        )->orderByDesc('created_at')->get();

        return view('admin.mensajes-contacto.index', [
            'mensajes' => $mensajes,
            'filtros' => $request->only('leido'),
        ]);
    }

    public function toggleLeido(MensajeContacto $mensaje): RedirectResponse
    {
        $mensaje->update(['leido' => ! $mensaje->leido]);

        return back();
    }

    public function destroy(MensajeContacto $mensaje): RedirectResponse
    {
        $mensaje->delete();

        return back();
    }
}

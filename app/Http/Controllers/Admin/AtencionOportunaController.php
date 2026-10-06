<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListaEspera;
use App\Models\SolicitudUrgencia;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AtencionOportunaController extends Controller
{
    public function index(): View
    {
        return view('admin.atencion-oportuna.index', [
            'urgencias' => SolicitudUrgencia::where('estado', 'pendiente')->latest()->get(),
            'espera' => ListaEspera::with(['servicio', 'tratamiento'])
                ->where('estado', 'pendiente')
                ->orderBy('fecha_preferida')
                ->latest()
                ->get(),
        ]);
    }

    public function contactarUrgencia(Request $request, SolicitudUrgencia $urgencia): RedirectResponse
    {
        $urgencia->update([
            'estado' => 'contactada',
            'contactado_por' => $request->user()->id,
            'fecha_contacto' => now(),
        ]);

        return back()->with('success', 'La solicitud de urgencia quedó marcada como contactada.');
    }

    public function contactarEspera(Request $request, ListaEspera $espera): RedirectResponse
    {
        $espera->update([
            'estado' => 'contactada',
            'contactado_por' => $request->user()->id,
            'fecha_contacto' => now(),
        ]);

        return back()->with('success', 'La persona quedó marcada como contactada.');
    }
}

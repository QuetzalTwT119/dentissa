<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListaEspera;
use App\Models\SolicitudUrgencia;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AtencionOportunaController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'historial_estado' => ['nullable', Rule::in(['contactada', 'cerrada'])],
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
        ]);

        $historial = fn ($query) => $query
            ->whereIn('estado', ['contactada', 'cerrada'])
            ->when($validated['historial_estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($validated['fecha_desde'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha_contacto', '>=', $fecha))
            ->when($validated['fecha_hasta'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha_contacto', '<=', $fecha));

        return view('admin.atencion-oportuna.index', [
            'urgencias' => SolicitudUrgencia::where('estado', 'pendiente')->latest()->get(),
            'espera' => ListaEspera::with(['servicio', 'tratamiento'])
                ->where('estado', 'pendiente')
                ->orderBy('fecha_preferida')
                ->latest()
                ->get(),
            'urgenciasHistorial' => $historial(SolicitudUrgencia::query())->latest('fecha_contacto')->get(),
            'esperaHistorial' => $historial(ListaEspera::with(['servicio', 'tratamiento']))->latest('fecha_contacto')->get(),
            'historialFiltros' => $request->only(['historial_estado', 'fecha_desde', 'fecha_hasta']),
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

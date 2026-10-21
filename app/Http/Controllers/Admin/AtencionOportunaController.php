<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListaEspera;
use App\Models\SolicitudUrgencia;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function contactarUrgencia(Request $request, SolicitudUrgencia $urgencia, AuditoriaService $auditoria): RedirectResponse
    {
        if ($urgencia->estado !== 'pendiente') {
            return back()->withErrors(['estado' => 'Esta solicitud ya fue atendida y no puede marcarse como contactada nuevamente.']);
        }

        DB::transaction(function () use ($request, $urgencia, $auditoria) {
            $estadoAnterior = $urgencia->estado;
            $urgencia->update([
                'estado' => 'contactada',
                'contactado_por' => $request->user()->id,
                'fecha_contacto' => now(),
            ]);

            $auditoria->registrar('urgencia.contactada', $urgencia, ['estado' => $estadoAnterior], [
                'estado' => $urgencia->estado,
            ], $request);
        });

        return back()->with('success', 'La solicitud de urgencia quedó marcada como contactada.');
    }

    public function contactarEspera(Request $request, ListaEspera $espera, AuditoriaService $auditoria): RedirectResponse
    {
        if ($espera->estado !== 'pendiente') {
            return back()->withErrors(['estado' => 'Esta solicitud ya fue atendida y no puede marcarse como contactada nuevamente.']);
        }

        DB::transaction(function () use ($request, $espera, $auditoria) {
            $estadoAnterior = $espera->estado;
            $espera->update([
                'estado' => 'contactada',
                'contactado_por' => $request->user()->id,
                'fecha_contacto' => now(),
            ]);

            $auditoria->registrar('lista_espera.contactada', $espera, ['estado' => $estadoAnterior], [
                'estado' => $espera->estado,
            ], $request);
        });

        return back()->with('success', 'La persona quedó marcada como contactada.');
    }
}

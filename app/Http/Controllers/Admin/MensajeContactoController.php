<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MensajeContacto;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MensajeContactoController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $this->filtros($request, [
            'leido' => ['nullable', 'in:0,1'],
        ]);

        $mensajes = MensajeContacto::when(
            filled($validated['leido'] ?? null),
            fn ($q) => $q->where('leido', $validated['leido'])
        )->orderByDesc('created_at')->paginate($this->paginationPageSize($request))->withQueryString();

        return view('admin.mensajes-contacto.index', [
            'mensajes' => $mensajes,
            'filtros' => $validated,
        ]);
    }

    public function toggleLeido(Request $request, MensajeContacto $mensaje, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            $mensaje->leido ? 'mensaje.marco_no_leido' : 'mensaje.marco_leido',
            $mensaje,
            ['leido'],
            fn () => $mensaje->update(['leido' => ! $mensaje->leido]),
        );

        return back()->with('success', $mensaje->fresh()->leido ? 'Mensaje marcado como leído.' : 'Mensaje marcado como no leído.');
    }

    public function destroy(Request $request, MensajeContacto $mensaje, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'mensaje.eliminado',
            $mensaje,
            ['leido'],
            fn () => $mensaje->delete(),
            eliminado: true,
        );

        return back()->with('success', 'Mensaje eliminado.');
    }
}

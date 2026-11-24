<?php

namespace App\Http\Controllers;

use App\Models\NotificacionSistema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificacionSistemaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $this->filtros($request, [
            'estado' => ['nullable', 'in:todas,pendientes,leidas'],
        ]);

        $consulta = $request->user()->notificacionesSistema();
        $notificaciones = (clone $consulta)
            ->when(($filtros['estado'] ?? 'todas') === 'pendientes', fn ($query) => $query->whereNull('leida_en'))
            ->when(($filtros['estado'] ?? 'todas') === 'leidas', fn ($query) => $query->whereNotNull('leida_en'))
            // La acción de cada aviso ("Ver en la agenda") necesita su cita.
            ->with('cita')
            ->latest()
            ->paginate($this->paginationPageSize($request))
            ->withQueryString();

        return view('notificaciones.index', [
            'notificaciones' => $notificaciones,
            'gruposNotificaciones' => $notificaciones->getCollection()->groupBy(function (NotificacionSistema $notificacion): string {
                return match (true) {
                    $notificacion->created_at->isToday() => 'Hoy',
                    $notificacion->created_at->isYesterday() => 'Ayer',
                    default => 'Anteriores',
                };
            }),
            'noLeidas' => $request->user()->notificacionesSistema()->whereNull('leida_en')->count(),
            'leidas' => $request->user()->notificacionesSistema()->whereNotNull('leida_en')->count(),
            'total' => $request->user()->notificacionesSistema()->count(),
            'estadoActual' => $filtros['estado'] ?? 'todas',
        ]);
    }

    public function actualizaciones(Request $request): JsonResponse
    {
        // Este sondeo corre en segundo plano cada pocos segundos. Si llega entre
        // una redirección y la página siguiente, consumiría los mensajes flash
        // ("Cita confirmada…", errores) sin mostrarlos; se conservan para la página.
        $request->session()->reflash();

        $validated = $request->validate([
            'desde_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $notificaciones = $request->user()->notificacionesSistema()
            ->where('id', '>', $validated['desde_id'] ?? 0)
            ->oldest('id')
            ->limit(20)
            ->with('cita.paciente')
            ->get();

        return response()->json([
            'no_vistas' => $request->user()->notificacionesSistema()->whereNull('vista_en')->count(),
            'ultima_id' => $notificaciones->max('id') ?? ($validated['desde_id'] ?? 0),
            'notificaciones' => $notificaciones->map(fn (NotificacionSistema $notificacion) => $notificacion->datosEnVivo())->values(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function marcarLeida(Request $request, NotificacionSistema $notificacion): RedirectResponse|JsonResponse
    {
        abort_unless($notificacion->user_id === $request->user()->id, 404);
        $notificacion->update(['leida_en' => now(), 'vista_en' => $notificacion->vista_en ?? now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'leida_en' => $notificacion->fresh()->leida_en?->toIso8601String(),
                'no_leidas' => $request->user()->notificacionesSistema()->whereNull('leida_en')->count(),
                'no_vistas' => $request->user()->notificacionesSistema()->whereNull('vista_en')->count(),
            ]);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    /**
     * "Ya lo atendí": el aviso sale de "Por atender" y pasa a informativas.
     * Devuelve el fragmento nuevo para que el panel lo mueva sin recargar.
     */
    public function marcarAtendida(Request $request, NotificacionSistema $notificacion): RedirectResponse|JsonResponse
    {
        abort_unless($notificacion->user_id === $request->user()->id, 404);
        abort_unless($notificacion->esPrioritaria(), 422);

        $notificacion->update([
            'atendida_en' => $notificacion->atendida_en ?? now(),
            'leida_en' => $notificacion->leida_en ?? now(),
            'vista_en' => $notificacion->vista_en ?? now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'html' => $notificacion->htmlPanel(),
                'por_atender' => $request->user()->notificacionesSistema()->porAtender()->count(),
                'no_vistas' => $request->user()->notificacionesSistema()->whereNull('vista_en')->count(),
            ]);
        }

        return back()->with('success', 'Aviso marcado como atendido.');
    }

    public function marcarPendiente(Request $request, NotificacionSistema $notificacion): RedirectResponse
    {
        abort_unless($notificacion->user_id === $request->user()->id, 404);
        $notificacion->update(['leida_en' => null]);

        return back()->with('success', 'Notificación marcada como pendiente.');
    }

    public function marcarTodasLeidas(Request $request): RedirectResponse|JsonResponse
    {
        $marcadas = $request->user()->notificacionesSistema()
            ->whereNull('leida_en')
            ->update(['leida_en' => now()]);
        // 25/11/2026: leer también es ver; antes la campana seguía con su número
        // aunque la lista dijera 0 pendientes (igual que marcarLeida()).
        $request->user()->notificacionesSistema()
            ->whereNull('vista_en')
            ->update(['vista_en' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'marcadas' => $marcadas,
                'no_leidas' => 0,
                'no_vistas' => 0,
            ]);
        }

        return back()->with('success', 'Notificaciones marcadas como leídas.');
    }

    /** Marca los avisos como vistos al abrir la campana, sin cerrar los pendientes. */
    public function marcarTodasVistas(Request $request): JsonResponse
    {
        $vistas = $request->user()->notificacionesSistema()
            ->whereNull('vista_en')
            ->update(['vista_en' => now()]);

        return response()->json([
            'vistas' => $vistas,
            'no_vistas' => 0,
        ]);
    }
}

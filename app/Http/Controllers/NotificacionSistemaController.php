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
        $filtros = $request->validate([
            'estado' => ['nullable', 'in:todas,pendientes,leidas'],
        ]);

        $consulta = $request->user()->notificacionesSistema();
        $notificaciones = (clone $consulta)
            ->when(($filtros['estado'] ?? 'todas') === 'pendientes', fn ($query) => $query->whereNull('leida_en'))
            ->when(($filtros['estado'] ?? 'todas') === 'leidas', fn ($query) => $query->whereNotNull('leida_en'))
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
        $validated = $request->validate([
            'desde_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $notificaciones = $request->user()->notificacionesSistema()
            ->where('id', '>', $validated['desde_id'] ?? 0)
            ->oldest('id')
            ->limit(20)
            ->get(['id', 'titulo', 'mensaje', 'url', 'created_at']);

        return response()->json([
            'no_vistas' => $request->user()->notificacionesSistema()->whereNull('vista_en')->count(),
            'ultima_id' => $notificaciones->max('id') ?? ($validated['desde_id'] ?? 0),
            'notificaciones' => $notificaciones->map(fn (NotificacionSistema $notificacion) => [
                'id' => $notificacion->id,
                'titulo' => $notificacion->titulo,
                'mensaje' => $notificacion->mensaje,
                'url' => $notificacion->url ?: route('notificaciones.index', [], false),
                'leida_url' => route('notificaciones.leida', $notificacion, false),
                'creada' => $notificacion->created_at->toIso8601String(),
            ])->values(),
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

        if ($request->expectsJson()) {
            return response()->json([
                'marcadas' => $marcadas,
                'no_leidas' => 0,
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

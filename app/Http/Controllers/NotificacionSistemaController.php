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
            ->paginate(15)
            ->withQueryString();

        return view('notificaciones.index', [
            'notificaciones' => $notificaciones,
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
            'no_leidas' => $request->user()->notificacionesSistema()->whereNull('leida_en')->count(),
            'ultima_id' => $notificaciones->max('id') ?? ($validated['desde_id'] ?? 0),
            'notificaciones' => $notificaciones->map(fn (NotificacionSistema $notificacion) => [
                'id' => $notificacion->id,
                'titulo' => $notificacion->titulo,
                'mensaje' => $notificacion->mensaje,
                'url' => $notificacion->url ?: route('notificaciones.index'),
                'creada' => $notificacion->created_at->toIso8601String(),
            ])->values(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function marcarLeida(Request $request, NotificacionSistema $notificacion): RedirectResponse
    {
        abort_unless($notificacion->user_id === $request->user()->id, 404);
        $notificacion->update(['leida_en' => now()]);

        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function marcarPendiente(Request $request, NotificacionSistema $notificacion): RedirectResponse
    {
        abort_unless($notificacion->user_id === $request->user()->id, 404);
        $notificacion->update(['leida_en' => null]);

        return back()->with('success', 'Notificación marcada como pendiente.');
    }

    public function marcarTodasLeidas(Request $request): RedirectResponse
    {
        $request->user()->notificacionesSistema()
            ->whereNull('leida_en')
            ->update(['leida_en' => now()]);

        return back()->with('success', 'Notificaciones marcadas como leídas.');
    }
}

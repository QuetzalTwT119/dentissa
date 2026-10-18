<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'accion' => ['nullable', 'string', 'max:50'],
            // Se acepta también el ID de una cuenta eliminada, cuyo registro
            // permanece en actor_user_id aunque ya no exista en users.
            'usuario_id' => ['nullable', 'integer', 'min:1'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $consulta = LogAuditoria::query()
            ->with('usuario:id,nombre,apellido')
            ->where(function ($query) {
                $query->whereNotNull('id_usuario')
                    ->orWhereIn('actor_rol', ['admin', 'asistente']);
            })
            ->when($filtros['accion'] ?? null, fn ($query, $accion) => $query->where('accion', $accion))
            ->when($filtros['usuario_id'] ?? null, fn ($query, $usuarioId) => $query->where(function ($subquery) use ($usuarioId) {
                $subquery->where('id_usuario', $usuarioId)
                    ->orWhere('actor_user_id', $usuarioId);
            }))
            ->when($filtros['desde'] ?? null, fn ($query, $desde) => $query->whereDate('fecha', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn ($query, $hasta) => $query->whereDate('fecha', '<=', $hasta))
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        return view('admin.auditoria.index', [
            'registros' => $consulta->paginate(20)->withQueryString(),
            'usuarios' => User::query()->orderBy('nombre')->orderBy('apellido')->get(['id', 'nombre', 'apellido']),
            'acciones' => LogAuditoria::query()->whereNotNull('accion')->distinct()->orderBy('accion')->pluck('accion'),
            'filtros' => $filtros,
        ]);
    }
}

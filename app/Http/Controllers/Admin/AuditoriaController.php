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
            'usuario' => ['nullable', 'integer', 'exists:users,id'],
            'accion' => ['nullable', 'in:creado,actualizado,eliminado'],
            'fecha' => ['nullable', 'date'],
        ]);

        $registros = LogAuditoria::query()
            ->with('usuario:id,nombre,apellido,rol')
            ->when($filtros['usuario'] ?? null, fn ($query, $usuario) => $query->where('id_usuario', $usuario))
            ->when($filtros['accion'] ?? null, fn ($query, $accion) => $query->where('accion', $accion))
            ->when($filtros['fecha'] ?? null, fn ($query, $fecha) => $query->whereDate('fecha', $fecha))
            ->orderByDesc('fecha')
            ->paginate(20)
            ->withQueryString();

        return view('admin.auditoria.index', [
            'registros' => $registros,
            'usuarios' => User::query()->orderBy('nombre')->orderBy('apellido')->get(['id', 'nombre', 'apellido']),
            'filtros' => $filtros,
            'etiquetas' => [
                'citas' => 'Citas',
                'pacientes' => 'Pacientes',
                'users' => 'Usuarios',
                'servicios' => 'Servicios',
                'tratamientos' => 'Tratamientos',
                'horarios_atencion' => 'Horarios',
                'historial_clinico' => 'Historial clínico',
                'ofertas' => 'Ofertas',
                'testimonios' => 'Testimonios',
                'imagenes' => 'Imágenes',
                'casos' => 'Casos clínicos',
                'preguntas_frecuentes' => 'Preguntas frecuentes',
                'mensajes_contacto' => 'Mensajes de contacto',
            ],
        ]);
    }
}

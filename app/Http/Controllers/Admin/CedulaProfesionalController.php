<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Support\Permisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;

/**
 * Cédulas profesionales en Configuración (26/11/2026; antes en Mi perfil, por
 * decisión del usuario). La doctora titular captura la de cada doctor: es la
 * que sale en sus recetas y consentimientos en PDF. Cada cambio queda en
 * Control de actividades.
 */
class CedulaProfesionalController extends Controller
{
    /**
     * Quienes atienden consultas: los que tienen el expediente (módulo "pacientes").
     * La doctora titular primero; luego en orden alfabético.
     */
    public static function doctores(): Collection
    {
        return User::query()
            ->where('activo', true)
            ->whereIn('rol', Permisos::valoresCon('pacientes'))
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get(['id', 'nombre', 'apellido', 'rol', 'cedula_profesional'])
            ->sortBy(fn (User $doctor) => $doctor->rol === RolUsuario::SuperAdmin ? 0 : 1)
            ->values();
    }

    public function __invoke(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $doctores = self::doctores()->keyBy('id');

        $datos = $request->validate([
            'cedulas' => ['array'],
            'cedulas.*' => ['nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z-]+$/'],
        ], [
            'cedulas.*.regex' => 'La cédula solo lleva números, letras y guiones.',
            'cedulas.*.max' => 'La cédula tiene como máximo 20 caracteres.',
        ]);

        $cambios = 0;
        foreach ($datos['cedulas'] ?? [] as $id => $cedula) {
            // Solo doctores activos: no se puede escribir en cualquier cuenta.
            $doctor = $doctores->get((int) $id);
            $cedula = filled($cedula) ? trim($cedula) : null;
            if (! $doctor || $doctor->cedula_profesional === $cedula) {
                continue;
            }

            $auditoria->registrarCambio(
                $request,
                'cedula.actualizada',
                $doctor,
                ['cedula_profesional'],
                fn () => $doctor->forceFill(['cedula_profesional' => $cedula])->save(),
            );
            $cambios++;
        }

        return Redirect::to(route('admin.configuracion').'#cedulas')
            ->with('success', $cambios === 0
                ? 'No había cédulas por cambiar.'
                : ($cambios === 1 ? 'Cédula profesional guardada.' : "{$cambios} cédulas profesionales guardadas."));
    }
}

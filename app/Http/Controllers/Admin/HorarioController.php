<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HorarioAtencion;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Redirect;

class HorarioController extends Controller
{
    // La pantalla vive en Configuración (ConfiguracionController); aquí solo se guarda.

    public function update(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $validated = $request->validate([
            'dias' => ['required', 'array', 'size:7'],
            'dias.*.dia_semana' => ['required', 'integer', 'between:0,6'],
            'dias.*.abierto' => ['boolean'],
            'dias.*.hora_apertura' => ['required', 'integer', 'between:0,23'],
            'dias.*.hora_cierre' => ['required', 'integer', 'between:0,23'],
            // 24/11/2026: un solo valor para todo el consultorio (antes uno por día).
            'capacidad_simultanea' => ['required', 'integer', 'between:1,20'],
        ], [
            'capacidad_simultanea.required' => 'Indica cuántos pacientes se atienden a la vez.',
            'capacidad_simultanea.between' => 'Pacientes a la vez debe ser entre 1 y 20.',
        ]);

        foreach ($validated['dias'] as $indice => $dia) {
            if (! empty($dia['abierto']) && $dia['hora_cierre'] <= $dia['hora_apertura']) {
                throw ValidationException::withMessages([
                    "dias.$indice.hora_cierre" => 'La hora de cierre debe ser después de la de apertura.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $validated, $auditoria) {
            foreach ($validated['dias'] as $dia) {
                $horario = HorarioAtencion::where('dia_semana', $dia['dia_semana'])->firstOrFail();
                $auditoria->registrarCambio(
                    $request,
                    'horario.actualizado',
                    $horario,
                    ['dia_semana', 'abierto', 'hora_apertura', 'hora_cierre', 'capacidad_simultanea'],
                    fn () => $horario->update([
                        'abierto' => $dia['abierto'] ?? false,
                        'hora_apertura' => $dia['hora_apertura'],
                        'hora_cierre' => $dia['hora_cierre'],
                        'capacidad_simultanea' => $validated['capacidad_simultanea'],
                    ]),
                );
            }
        });

        return Redirect::to(route('admin.configuracion').'#horarios')->with('success', 'Horarios guardados. La agenda en línea ya usa los nuevos.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Services\CitaReglasService;
use App\Services\DisponibilidadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AsistenteAgendaController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Cita::class);

        return view('asistente-agenda.create', $this->datosFormulario());
    }

    public function proponer(Request $request, DisponibilidadService $disponibilidad, CitaReglasService $reglas): View
    {
        Gate::authorize('create', Cita::class);

        $validated = $request->validate([
            'id_paciente' => ['required', 'exists:pacientes,id'],
            'id_servicio' => ['required', 'exists:servicios,id'],
            'id_tratamiento' => ['required', 'exists:tratamientos,id'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'motivo' => ['nullable', 'string', 'max:1000'],
        ], [
            'fecha.after_or_equal' => 'Selecciona hoy o una fecha futura para buscar horarios.',
        ]);

        [, $tratamiento] = $reglas->validarCatalogo($validated['id_servicio'], $validated['id_tratamiento']);

        return view('asistente-agenda.create', [
            ...$this->datosFormulario(),
            'propuesta' => [
                'paciente' => Paciente::findOrFail($validated['id_paciente']),
                'tratamiento' => $tratamiento,
                'id_servicio' => $validated['id_servicio'],
                'fecha' => $validated['fecha'],
                'motivo' => $validated['motivo'] ?? null,
                'horarios' => $disponibilidad->horariosDisponibles(
                    $validated['fecha'],
                    $tratamiento->duracion_estimada,
                ),
            ],
        ]);
    }

    private function datosFormulario(): array
    {
        return [
            'pacientes' => Paciente::select('id', 'nombre', 'apellido')->orderBy('nombre')->get(),
            'servicios' => Servicio::where('activo', true)
                ->with(['tratamientos' => fn ($query) => $query->where('activo', true)->orderBy('nombre')])
                ->orderBy('orden')
                ->get(),
        ];
    }
}

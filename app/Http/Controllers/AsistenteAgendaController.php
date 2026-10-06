<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Services\DisponibilidadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class AsistenteAgendaController extends Controller
{
    public function create(): View
    {
        Gate::authorize('create', Cita::class);

        return view('asistente-agenda.create', $this->datosFormulario());
    }

    public function proponer(Request $request, DisponibilidadService $disponibilidad): View
    {
        Gate::authorize('create', Cita::class);

        $validated = $request->validate([
            'id_paciente' => ['required', 'exists:pacientes,id'],
            'id_servicio' => ['required', 'exists:servicios,id'],
            'id_tratamiento' => ['required', 'exists:tratamientos,id'],
            'fecha' => ['required', 'date'],
            'motivo' => ['nullable', 'string', 'max:1000'],
        ]);

        $tratamiento = Tratamiento::findOrFail($validated['id_tratamiento']);

        if (! $tratamiento->servicios()->whereKey($validated['id_servicio'])->exists()) {
            throw ValidationException::withMessages([
                'id_tratamiento' => 'El tratamiento seleccionado no pertenece a ese servicio.',
            ]);
        }

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

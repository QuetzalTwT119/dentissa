<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Tratamiento;
use App\Services\DisponibilidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DisponibilidadController extends Controller
{
    public function index(Request $request, DisponibilidadService $disponibilidad): JsonResponse
    {
        $validated = $request->validate([
            'tratamiento_id' => ['required', 'exists:tratamientos,id'],
            'fecha' => ['required', 'date'],
            'exclude_cita_id' => ['nullable', 'exists:citas,id'],
        ]);

        $excludeCitaId = null;

        if (isset($validated['exclude_cita_id'])) {
            // Solo el personal autenticado que edita una cita puede excluirla
            // del cálculo. La agenda pública nunca debe poder simular que la
            // cita de otra persona no ocupa un horario.
            abort_unless($request->user(), 403);

            $citaExcluida = Cita::findOrFail($validated['exclude_cita_id']);
            Gate::authorize('update', $citaExcluida);
            $excludeCitaId = $citaExcluida->id;
        }

        $tratamiento = Tratamiento::query()
            ->whereKey($validated['tratamiento_id'])
            ->where('activo', true)
            ->first();

        if (! $tratamiento) {
            throw ValidationException::withMessages([
                'tratamiento_id' => 'El tratamiento seleccionado no existe o está inactivo.',
            ]);
        }

        $horarios = $disponibilidad->horariosDisponibles(
            $validated['fecha'],
            $tratamiento,
            $excludeCitaId,
        );

        return response()->json(['horarios' => $horarios]);
    }
}

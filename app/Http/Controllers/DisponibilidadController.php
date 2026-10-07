<?php

namespace App\Http\Controllers;

use App\Models\Tratamiento;
use App\Services\DisponibilidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            $validated['exclude_cita_id'] ?? null,
        );

        return response()->json(['horarios' => $horarios]);
    }
}

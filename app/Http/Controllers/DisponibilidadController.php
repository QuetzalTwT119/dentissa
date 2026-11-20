<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Tratamiento;
use App\Services\DisponibilidadService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DisponibilidadController extends Controller
{
    /** Cuántos meses hacia adelante puede consultar el calendario. */
    public const MESES_ADELANTE = 12;

    /** Horarios libres de un día (la lista que se muestra al elegir una fecha). */
    public function index(Request $request, DisponibilidadService $disponibilidad): JsonResponse
    {
        $validated = $request->validate([
            'tratamiento_id' => ['required', 'exists:tratamientos,id'],
            'fecha' => ['required', 'date'],
            'exclude_cita_id' => ['nullable', 'exists:citas,id'],
        ]);

        $horarios = $disponibilidad->horariosDisponibles(
            $validated['fecha'],
            $this->tratamientoActivo($validated['tratamiento_id']),
            $this->citaExcluida($request, $validated['exclude_cita_id'] ?? null),
        );

        return response()->json(['horarios' => $horarios]);
    }

    /** Resumen de un mes para el calendario: horarios libres por día. */
    public function mes(Request $request, DisponibilidadService $disponibilidad): JsonResponse
    {
        $validated = $request->validate([
            'tratamiento_id' => ['required', 'exists:tratamientos,id'],
            'mes' => ['required', 'date_format:Y-m'],
            'exclude_cita_id' => ['nullable', 'exists:citas,id'],
        ]);

        // Solo del mes actual a MESES_ADELANTE: cada día del mes se calcula
        // contra la agenda, así que no se permite recorrer años completos.
        $mes = CarbonImmutable::createFromFormat('!Y-m', $validated['mes']);
        $mesActual = CarbonImmutable::today()->startOfMonth();

        if ($mes->lt($mesActual) || $mes->gt($mesActual->addMonths(self::MESES_ADELANTE))) {
            throw ValidationException::withMessages([
                'mes' => 'Solo se puede consultar del mes actual a los próximos '.self::MESES_ADELANTE.' meses.',
            ]);
        }

        $tratamiento = $this->tratamientoActivo($validated['tratamiento_id']);

        return response()->json([
            'mes' => $mes->format('Y-m'),
            'duracion_minutos' => (int) $tratamiento->duracion_estimada,
            'dias' => $disponibilidad->resumenMensual(
                $mes,
                $tratamiento,
                $this->citaExcluida($request, $validated['exclude_cita_id'] ?? null),
            ),
        ]);
    }

    private function tratamientoActivo(int|string $id): Tratamiento
    {
        $tratamiento = Tratamiento::query()
            ->whereKey($id)
            ->where('activo', true)
            ->first();

        if (! $tratamiento) {
            throw ValidationException::withMessages([
                'tratamiento_id' => 'El tratamiento seleccionado no existe o está inactivo.',
            ]);
        }

        return $tratamiento;
    }

    /**
     * Solo el personal autenticado que edita una cita puede excluirla del
     * cálculo. La agenda pública nunca debe poder simular que la cita de otra
     * persona no ocupa un horario.
     */
    private function citaExcluida(Request $request, int|string|null $id): ?int
    {
        if ($id === null) {
            return null;
        }

        abort_unless($request->user(), 403);

        $cita = Cita::findOrFail($id);
        Gate::authorize('update', $cita);

        return $cita->id;
    }
}

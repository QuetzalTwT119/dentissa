<?php

namespace App\Services;

use App\Models\Servicio;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class CitaReglasService
{
    /**
     * Valida el catalogo completo antes de que cualquier canal cree una cita.
     *
     * @return array{0: Servicio, 1: Tratamiento}
     */
    public function validarCatalogo(int|string $idServicio, int|string $idTratamiento): array
    {
        $servicio = Servicio::query()
            ->whereKey($idServicio)
            ->where('activo', true)
            ->first();

        if (! $servicio) {
            throw ValidationException::withMessages([
                'id_servicio' => 'El servicio seleccionado no existe o está inactivo.',
            ]);
        }

        $tratamiento = Tratamiento::query()
            ->whereKey($idTratamiento)
            ->where('activo', true)
            ->first();

        if (! $tratamiento) {
            throw ValidationException::withMessages([
                'id_tratamiento' => 'El tratamiento seleccionado no existe o está inactivo.',
            ]);
        }

        if (! $servicio->tratamientos()->whereKey($tratamiento->id)->exists()) {
            throw ValidationException::withMessages([
                'id_tratamiento' => 'El tratamiento seleccionado no pertenece al servicio elegido.',
            ]);
        }

        return [$servicio, $tratamiento];
    }

    public function validarFechaFutura(CarbonImmutable|Carbon $fechaHora): void
    {
        if (! $fechaHora->isFuture()) {
            throw ValidationException::withMessages([
                'hora' => 'La fecha y hora de la cita deben estar en el futuro.',
            ]);
        }
    }
}

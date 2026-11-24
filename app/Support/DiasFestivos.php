<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Días festivos en que el consultorio no trabaja (decisión del usuario,
 * 24/11/2026): los de descanso obligatorio del artículo 74 de la Ley Federal
 * del Trabajo, más Día de Muertos, Virgen de Guadalupe, Nochebuena y fin de año.
 *
 * Tres festivos de ley caen en lunes y cambian cada año (Constitución, Benito
 * Juárez y Revolución), por eso se calculan y no se escriben como fechas fijas.
 * El 1 de octubre solo es festivo cuando hay cambio de Presidente (cada 6 años:
 * 2024, 2030, 2036…).
 */
class DiasFestivos
{
    /** Primer año con cambio de Presidente el 1 de octubre (reforma de 2014). */
    private const PRIMERA_TRANSMISION = 2024;

    /**
     * Festivos de un año, ordenados por fecha.
     *
     * @return array<string, string> fecha (Y-m-d) => nombre
     */
    public static function delAnio(int $anio): array
    {
        $lunes = fn (int $mes, int $numero) => Carbon::create($anio, $mes, 1)
            ->nthOfMonth($numero, Carbon::MONDAY)
            ->toDateString();

        $festivos = [
            sprintf('%d-01-01', $anio) => 'Año Nuevo',
            $lunes(2, 1) => 'Día de la Constitución',
            $lunes(3, 3) => 'Natalicio de Benito Juárez',
            sprintf('%d-05-01', $anio) => 'Día del Trabajo',
            sprintf('%d-09-16', $anio) => 'Día de la Independencia',
            sprintf('%d-11-02', $anio) => 'Día de Muertos',
            $lunes(11, 3) => 'Día de la Revolución',
            sprintf('%d-12-12', $anio) => 'Día de la Virgen de Guadalupe',
            sprintf('%d-12-24', $anio) => 'Nochebuena',
            sprintf('%d-12-25', $anio) => 'Navidad',
            sprintf('%d-12-31', $anio) => 'Fin de año',
        ];

        if ($anio >= self::PRIMERA_TRANSMISION && ($anio - self::PRIMERA_TRANSMISION) % 6 === 0) {
            $festivos[sprintf('%d-10-01', $anio)] = 'Cambio de Presidente de la República';
        }

        ksort($festivos);

        return $festivos;
    }
}

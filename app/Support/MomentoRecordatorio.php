<?php

namespace App\Support;

use App\Models\BloqueoAgenda;
use App\Models\HorarioAtencion;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Cuándo sale el recordatorio de una cita (24/11/2026): el día hábil anterior,
 * una hora después de abrir. Antes salía exactamente 24 h antes: a veces de
 * noche o en domingo, y si el paciente cancelaba nadie lo veía hasta el día
 * siguiente.
 *
 * "Día hábil" = abierto en Horarios de atención y sin bloqueo de día completo.
 * Ejemplo (lunes a viernes de 9 a 18): cita el martes → lunes 10:00; cita el
 * lunes → viernes 10:00.
 */
class MomentoRecordatorio
{
    public const HORAS_DESPUES_DE_ABRIR = 1;

    /** Si en una semana no hay un día abierto, se usa 24 h antes. */
    private const DIAS_A_REVISAR = 7;

    /** @param Collection<int, HorarioAtencion>|null $horarios por dia_semana */
    public static function para(CarbonInterface $fechaCita, ?Collection $horarios = null): Carbon
    {
        $horarios ??= HorarioAtencion::all()->keyBy('dia_semana');
        $diaCita = Carbon::parse($fechaCita)->startOfDay();

        for ($atras = 1; $atras <= self::DIAS_A_REVISAR; $atras++) {
            $dia = $diaCita->copy()->subDays($atras);
            $horario = $horarios->get($dia->dayOfWeek);

            if (! $horario || ! $horario->abierto || self::bloqueadoTodoElDia($dia)) {
                continue;
            }

            // Una hora después de abrir, sin pasar del cierre.
            $hora = $horario->hora_apertura + self::HORAS_DESPUES_DE_ABRIR < $horario->hora_cierre
                ? $horario->hora_apertura + self::HORAS_DESPUES_DE_ABRIR
                : $horario->hora_apertura;

            return $dia->setTime($hora, 0);
        }

        return Carbon::parse($fechaCita)->subDay();
    }

    private static function bloqueadoTodoElDia(Carbon $dia): bool
    {
        return BloqueoAgenda::delDia($dia->toDateString())->get()->contains(fn (BloqueoAgenda $bloqueo) => $bloqueo->todoElDia());
    }
}

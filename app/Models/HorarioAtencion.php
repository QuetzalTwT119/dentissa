<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable(['dia_semana', 'abierto', 'hora_apertura', 'hora_cierre', 'capacidad_simultanea'])]
class HorarioAtencion extends Model
{
    protected $table = 'horarios_atencion';

    protected function casts(): array
    {
        return [
            'abierto' => 'boolean',
        ];
    }

    public static function paraFecha(string $fecha, bool $lock = false): ?self
    {
        return static::where('dia_semana', Carbon::parse($fecha)->dayOfWeek)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();
    }

    /**
     * Resume el horario configurado para mostrarlo a pacientes, agrupando días
     * consecutivos con el mismo horario: "Lunes a sábado de 8:00 a 18:00 hrs."
     */
    public static function resumenSemanal(): string
    {
        $nombres = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 0 => 'domingo'];
        $horarios = static::where('abierto', true)->get()->keyBy('dia_semana');

        $grupos = [];
        foreach (array_keys($nombres) as $dia) {
            $horario = $horarios->get($dia);
            $ultimo = array_key_last($grupos);

            if (! $horario) {
                $grupos[] = null;

                continue;
            }

            $rango = "{$horario->hora_apertura}:00 a {$horario->hora_cierre}:00";

            if ($ultimo !== null && $grupos[$ultimo] !== null && $grupos[$ultimo]['rango'] === $rango) {
                $grupos[$ultimo]['fin'] = $dia;
            } else {
                $grupos[] = ['inicio' => $dia, 'fin' => $dia, 'rango' => $rango];
            }
        }

        $partes = collect($grupos)->filter()->map(function (array $grupo) use ($nombres) {
            $dias = $grupo['inicio'] === $grupo['fin']
                ? $nombres[$grupo['inicio']]
                : "{$nombres[$grupo['inicio']]} a {$nombres[$grupo['fin']]}";

            return ucfirst($dias)." de {$grupo['rango']} hrs";
        });

        return $partes->isEmpty()
            ? 'Consulta la disponibilidad al elegir el día.'
            : $partes->join(' • ').'.';
    }
}

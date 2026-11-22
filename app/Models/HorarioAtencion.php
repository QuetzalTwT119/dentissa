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
     * Estado del consultorio en este momento, para la portada:
     * "Abierto hoy hasta las 18:00", "Abre hoy a las 8:00" o
     * "Cerrado ahora · abre mañana a las 9:00". Usa la hora de la clínica
     * (APP_TIMEZONE). Devuelve null si ningún día está abierto.
     *
     * @return array{abierto: bool, texto: string}|null
     */
    public static function estadoActual(?Carbon $ahora = null): ?array
    {
        $ahora ??= now();
        $semana = static::where('abierto', true)->get()->keyBy('dia_semana');
        $hoy = $semana->get($ahora->dayOfWeek);

        if ($hoy && $ahora->hour < $hoy->hora_cierre) {
            return $ahora->hour < $hoy->hora_apertura
                ? ['abierto' => false, 'texto' => "Abre hoy a las {$hoy->hora_apertura}:00"]
                : ['abierto' => true, 'texto' => "Abierto hoy hasta las {$hoy->hora_cierre}:00"];
        }

        for ($dias = 1; $dias <= 7; $dias++) {
            $dia = $ahora->copy()->addDays($dias);
            $siguiente = $semana->get($dia->dayOfWeek);

            if ($siguiente) {
                $cuando = $dias === 1 ? 'mañana' : 'el '.$dia->locale('es')->dayName;

                return ['abierto' => false, 'texto' => "Cerrado ahora · abre {$cuando} a las {$siguiente->hora_apertura}:00"];
            }
        }

        return null;
    }

    /**
     * Resume el horario configurado para mostrarlo a pacientes, agrupando días
     * consecutivos con el mismo horario: "Lunes a sábado de 8:00 a 18:00 hrs."
     */
    public static function resumenSemanal(): string
    {
        $lineas = static::lineasSemanales();

        return $lineas === []
            ? 'Consulta la disponibilidad al elegir el día.'
            : implode(' • ', $lineas).'.';
    }

    /**
     * Mismo agrupado que resumenSemanal(), una línea por grupo:
     * ["Lunes a viernes de 8:00 a 18:00 hrs", "Sábado de 9:00 a 14:00 hrs"].
     *
     * @return list<string>
     */
    public static function lineasSemanales(): array
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

        return $partes->values()->all();
    }
}

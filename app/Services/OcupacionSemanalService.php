<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\HorarioAtencion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Mapa de ocupación de la vista semanal ("Bloques"): cada día se divide en
 * bloques de 2 horas y de cada bloque se calcula qué tan lleno está, en qué
 * horarios todavía cabe una cita y qué citas lo ocupan.
 *
 * Es una vista de consulta: no reserva nada. Para agendar, la cita vuelve a
 * validarse en DisponibilidadService con su tratamiento y margen reales.
 */
class OcupacionSemanalService
{
    public const HORAS_POR_BLOQUE = 2;

    /**
     * @param  Collection<int, array{fecha: Carbon, abierto: bool, citas: Collection<int, Cita>}>  $dias
     * @param  Collection<int, HorarioAtencion>  $horarios  indexados por dia_semana
     * @return array{horas: array<int, int>, bloques: array<string, array<string, mixed>>, cerrados: array<string, Collection<int, Cita>>, seleccion: ?string}
     */
    public function calcular(Collection $dias, Collection $horarios): array
    {
        $abiertos = $dias->filter(fn (array $dia) => $dia['abierto'])
            ->map(fn (array $dia) => $horarios->get($dia['fecha']->dayOfWeek));

        $desde = (int) ($abiertos->min('hora_apertura') ?? 8);
        $hasta = (int) ($abiertos->max('hora_cierre') ?? 18);
        $horas = range($desde, $hasta - 1, self::HORAS_POR_BLOQUE);
        $intervalo = (int) config('dentissa.intervalo_minutos', 30);
        $ahora = now();

        $bloques = [];
        $cerrados = [];
        $primero = null;
        $siguiente = null;

        foreach ($dias as $dia) {
            $horario = $dia['abierto'] ? $horarios->get($dia['fecha']->dayOfWeek) : null;

            // Un día cerrado puede tener citas (agendadas antes de cerrarlo): no deben perderse de vista.
            if (! $horario && $dia['citas']->isNotEmpty()) {
                $cerrados[$dia['fecha']->toDateString()] = $dia['citas'];
            }

            foreach ($horas as $hora) {
                $clave = $dia['fecha']->toDateString().'|'.$hora;

                if (! $horario) {
                    $bloques[$clave] = null;

                    continue;
                }

                $inicio = max($hora, $horario->hora_apertura) * 60;
                $fin = min($hora + self::HORAS_POR_BLOQUE, $horario->hora_cierre) * 60;

                if ($fin <= $inicio) {
                    $bloques[$clave] = null;

                    continue;
                }

                $bloques[$clave] = $this->bloque($dia['fecha'], $inicio, $fin, $dia['citas'], (int) $horario->capacidad_simultanea, $intervalo, $ahora);
                $bloques[$clave]['horario'] = "{$horario->hora_apertura}:00 – {$horario->hora_cierre}:00";

                // Bloque que se abre al entrar: el primero que todavía no termina
                // (el actual si es hoy, o el siguiente aunque sea mañana). Si toda la
                // semana ya pasó, el primero abierto.
                $primero ??= $clave;
                if ($siguiente === null && ! $bloques[$clave]['pasado']) {
                    $siguiente = $clave;
                }
            }
        }

        $seleccion = $siguiente ?? $primero;

        return ['horas' => $horas, 'bloques' => $bloques, 'cerrados' => $cerrados, 'seleccion' => $seleccion];
    }

    /** @param Collection<int, Cita> $citas */
    private function bloque(Carbon $fecha, int $inicio, int $fin, Collection $citas, int $capacidad, int $intervalo, Carbon $ahora): array
    {
        $minutosDe = fn (Cita $cita) => $cita->fecha_hora->hour * 60 + $cita->fecha_hora->minute;
        $seCruza = fn (Cita $cita, int $a, int $b) => $minutosDe($cita) < $b && $minutosDe($cita) + (int) $cita->duracion_estimada > $a;

        $enBloque = $citas->filter(fn (Cita $cita) => $seCruza($cita, $inicio, $fin))->values();
        $activas = $enBloque->reject(fn (Cita $cita) => $cita->estado->esInactiva());

        $ocupados = $activas->sum(fn (Cita $cita) => max(0, min($minutosDe($cita) + (int) $cita->duracion_estimada, $fin) - max($minutosDe($cita), $inicio)));
        $ocupacion = $ocupados / (($fin - $inicio) * max($capacidad, 1));

        // Horarios con lugar: inicios que aún no pasan y donde hay menos citas que la capacidad.
        $huecos = [];
        for ($t = $inicio; $t + $intervalo <= $fin; $t += $intervalo) {
            if ($fecha->copy()->startOfDay()->addMinutes($t)->lte($ahora)) {
                continue;
            }

            if ($activas->filter(fn (Cita $cita) => $seCruza($cita, $t, $t + $intervalo))->count() < $capacidad) {
                $huecos[] = sprintf('%02d:%02d', intdiv($t, 60), $t % 60);
            }
        }

        $pasado = $fecha->copy()->startOfDay()->addMinutes($fin)->lte($ahora);

        return [
            'fecha' => $fecha,
            'inicio' => $inicio,
            'fin' => $fin,
            'citas' => $enBloque,
            'activas' => $activas->count(),
            'ocupacion' => $ocupacion,
            'huecos' => $huecos,
            'pasado' => $pasado,
            // libre: nada agendado · con_citas: queda lugar · lleno: ya no cabe nadie (o ≥ 90 % ocupado).
            'nivel' => $activas->isEmpty() ? 'libre' : ($ocupacion >= 0.9 || (! $pasado && $huecos === []) ? 'lleno' : 'con_citas'),
        ];
    }
}

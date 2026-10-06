<?php

namespace App\Services;

use App\Enums\EstadoCita;
use App\Enums\EstadoTestimonio;
use App\Enums\TipoConsulta;
use App\Models\Cita;
use App\Models\HistorialClinico;
use App\Models\HorarioAtencion;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Testimonio;
use App\Models\Tratamiento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReporteService
{
    public function generar(string $fechaInicio, string $fechaFin): array
    {
        $inicio = Carbon::parse($fechaInicio)->startOfDay();
        $fin = Carbon::parse($fechaFin)->endOfDay();

        return [
            'rango' => [
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $fin->toDateString(),
            ],
            'pacientes' => $this->pacientes($inicio, $fin),
            'citas' => $this->citas($inicio, $fin),
            'catalogo' => $this->catalogo($inicio, $fin),
            'historial' => $this->historial($inicio, $fin),
            'eficiencia' => $this->eficiencia($inicio, $fin),
            'ocupacion' => $this->ocupacion($inicio, $fin),
            'satisfaccion' => $this->satisfaccion($inicio, $fin),
        ];
    }

    private function pacientes(Carbon $inicio, Carbon $fin): array
    {
        return [
            'total' => Paciente::count(),
            'nuevos' => Paciente::whereBetween('created_at', [$inicio, $fin])->count(),
        ];
    }

    private function citas(Carbon $inicio, Carbon $fin): array
    {
        $citas = Cita::whereBetween('fecha_hora', [$inicio, $fin])->get(['estado']);

        $porEstado = collect(EstadoCita::cases())->mapWithKeys(fn (EstadoCita $estado) => [
            $estado->value => $citas->where('estado', $estado)->count(),
        ]);

        $total = $citas->count();
        $noAsistio = $porEstado->get(EstadoCita::NoAsistio->value, 0);

        return [
            'total' => $total,
            'por_estado' => $porEstado,
            'tasa_inasistencia' => $total > 0 ? round($noAsistio / $total * 100, 1) : 0,
        ];
    }

    private function catalogo(Carbon $inicio, Carbon $fin): array
    {
        $masSolicitados = Cita::whereBetween('fecha_hora', [$inicio, $fin])
            ->whereNotNull('id_tratamiento')
            ->selectRaw('id_tratamiento, count(*) as total')
            ->groupBy('id_tratamiento')
            ->orderByDesc('total')
            ->take(10)
            ->with('tratamiento:id,nombre')
            ->get();

        return [
            'tratamientos_mas_solicitados' => $masSolicitados->map(fn ($fila) => [
                'nombre' => $fila->tratamiento->nombre ?? 'N/D',
                'total' => $fila->total,
            ])->values(),
            'servicios_activos' => Servicio::where('activo', true)->count(),
            'tratamientos_activos' => Tratamiento::where('activo', true)->count(),
        ];
    }

    private function historial(Carbon $inicio, Carbon $fin): array
    {
        $consultas = HistorialClinico::whereBetween('fecha_consulta', [$inicio, $fin])->get(['tipo_consulta']);

        return [
            'total' => $consultas->count(),
            'por_tipo' => collect(TipoConsulta::cases())->mapWithKeys(fn (TipoConsulta $tipo) => [
                $tipo->value => $consultas->where('tipo_consulta', $tipo)->count(),
            ]),
        ];
    }

    private function eficiencia(Carbon $inicio, Carbon $fin): array
    {
        $citasCompletadas = Cita::whereBetween('fecha_hora', [$inicio, $fin])
            ->where('estado', EstadoCita::Completada)
            ->count();

        $horasTotales = 0;

        foreach ($this->horariosEnRango($inicio, $fin) as $horario) {
            $horasTotales += max($horario->hora_cierre - $horario->hora_apertura, 0);
        }

        $bloques = $horasTotales > 0 ? $horasTotales / 1.5 : 0;
        $citasPorHora = $horasTotales > 0 ? $citasCompletadas / $horasTotales : 0;

        return [
            'citas_completadas' => $citasCompletadas,
            'bloques_hora_y_media' => round($bloques, 1),
            'eficiencia' => $bloques > 0 ? round($citasCompletadas / $bloques, 2) : 0,
            'comparativa_gerin' => round($citasPorHora / 1, 2),
        ];
    }

    private function ocupacion(Carbon $inicio, Carbon $fin): array
    {
        $intervalo = config('dentissa.intervalo_minutos');
        $capacidadTotal = 0;

        foreach ($this->horariosEnRango($inicio, $fin) as $horario) {
            $minutosDia = max(($horario->hora_cierre - $horario->hora_apertura) * 60, 0);
            $slotsDia = $intervalo > 0 ? intdiv($minutosDia, $intervalo) : 0;
            $capacidadTotal += $slotsDia * $horario->capacidad_simultanea;
        }

        $citasAtendidas = Cita::whereBetween('fecha_hora', [$inicio, $fin])
            ->where('estado', EstadoCita::Completada)
            ->count();

        return [
            'capacidad_total' => $capacidadTotal,
            'citas_atendidas' => $citasAtendidas,
            'tasa_ocupacion' => $capacidadTotal > 0 ? round($citasAtendidas / $capacidadTotal * 100, 1) : 0,
        ];
    }

    /**
     * Devuelve el HorarioAtencion de cada día abierto dentro del rango (uno por
     * cada fecha del calendario, repitiendo el mismo día de la semana las veces
     * que corresponda), sin consultar la BD más de una vez.
     *
     * @return Collection<int, HorarioAtencion>
     */
    private function horariosEnRango(Carbon $inicio, Carbon $fin): Collection
    {
        $horariosPorDia = HorarioAtencion::where('abierto', true)->get()->keyBy('dia_semana');

        $resultado = collect();
        $cursor = $inicio->copy()->startOfDay();
        $finDia = $fin->copy()->startOfDay();

        while ($cursor->lte($finDia)) {
            if ($horario = $horariosPorDia->get($cursor->dayOfWeek)) {
                $resultado->push($horario);
            }

            $cursor->addDay();
        }

        return $resultado;
    }

    private function satisfaccion(Carbon $inicio, Carbon $fin): ?float
    {
        $promedio = Testimonio::where('estado', EstadoTestimonio::Aprobado)
            ->whereBetween('created_at', [$inicio, $fin])
            ->avg('calificacion');

        return $promedio !== null ? round((float) $promedio, 1) : null;
    }
}

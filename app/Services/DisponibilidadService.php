<?php

namespace App\Services;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Tratamiento;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DisponibilidadService
{
    /**
     * Devuelve los horarios disponibles (formato H:i) para una fecha y duración dadas.
     */
    public function horariosDisponibles(string $fecha, Tratamiento|int $tratamiento, ?int $excludeCitaId = null): array
    {
        $horario = HorarioAtencion::paraFecha($fecha);

        if (! $horario || ! $horario->abierto) {
            return [];
        }

        $apertura = Carbon::parse($fecha)->setTime($horario->hora_apertura, 0);
        $cierre = Carbon::parse($fecha)->setTime($horario->hora_cierre, 0);
        $intervalo = config('dentissa.intervalo_minutos');
        $duracionMinutos = $this->duracionMinutos($tratamiento);
        $margenMinutos = $this->margenMinutos($tratamiento);

        $citasDelDia = $this->citasDelDia($fecha, $excludeCitaId);

        $horarios = [];
        $candidato = $apertura->copy();

        while ($candidato->copy()->addMinutes($duracionMinutos + $margenMinutos)->lte($cierre)) {
            if ($candidato->isFuture() && $this->tieneCupo($candidato, $duracionMinutos, $margenMinutos, $citasDelDia, $horario->capacidad_simultanea)) {
                $horarios[] = $candidato->format('H:i');
            }

            $candidato->addMinutes($intervalo);
        }

        return $horarios;
    }

    /**
     * Revalida en el servidor que un horario puntual siga disponible antes de guardar.
     *
     * Debe llamarse dentro de un DB::transaction() junto con el create()/update() de la
     * cita. Primero bloquea la fila de configuración del día de la semana, que existe
     * aunque aún no haya citas; así las reservas de ese día se revisan una por una.
     * Después bloquea y consulta las citas del día para contar los solapamientos.
     */
    public function estaDisponible(CarbonImmutable|Carbon $fechaHora, Tratamiento|int $tratamiento, ?int $excludeCitaId = null): bool
    {
        if (! $fechaHora->isFuture()) {
            return false;
        }

        $horario = HorarioAtencion::paraFecha($fechaHora->format('Y-m-d'), lock: true);

        if (! $horario || ! $horario->abierto) {
            return false;
        }

        $citasDelDia = $this->citasDelDia($fechaHora->format('Y-m-d'), $excludeCitaId, lock: true);

        return $this->tieneCupo(
            Carbon::parse($fechaHora),
            $this->duracionMinutos($tratamiento),
            $this->margenMinutos($tratamiento),
            $citasDelDia,
            $horario->capacidad_simultanea,
        );
    }

    private function citasDelDia(string $fecha, ?int $excludeCitaId, bool $lock = false): Collection
    {
        return Cita::whereDate('fecha_hora', $fecha)
            ->where('estado', '!=', EstadoCita::Cancelada)
            ->when($excludeCitaId, fn ($query) => $query->where('id', '!=', $excludeCitaId))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->with('tratamiento:id,categoria,duracion_estimada')
            ->get(['id', 'id_tratamiento', 'fecha_hora', 'duracion_estimada']);
    }

    private function tieneCupo(Carbon $inicioCandidato, int $duracionMinutos, int $margenMinutos, Collection $citasDelDia, int $capacidadSimultanea): bool
    {
        $finCandidato = $inicioCandidato->copy()->addMinutes($duracionMinutos + $margenMinutos);

        $solapadas = $citasDelDia->filter(function (Cita $cita) use ($inicioCandidato, $finCandidato) {
            $inicioCita = Carbon::parse($cita->fecha_hora);
            $finCita = $inicioCita->copy()->addMinutes(
                $cita->duracion_estimada + $this->margenMinutos($cita->tratamiento ?? $cita->duracion_estimada),
            );

            return $inicioCandidato->lt($finCita) && $finCandidato->gt($inicioCita);
        });

        return $solapadas->count() < $capacidadSimultanea;
    }

    private function duracionMinutos(Tratamiento|int $tratamiento): int
    {
        return max($tratamiento instanceof Tratamiento ? (int) $tratamiento->duracion_estimada : $tratamiento, 0);
    }

    private function margenMinutos(Tratamiento|int $tratamiento): int
    {
        $duracion = $this->duracionMinutos($tratamiento);
        $esQuirurgico = $tratamiento instanceof Tratamiento
            && mb_strtolower((string) $tratamiento->categoria) === 'quirúrgica';

        if (! $esQuirurgico && $duracion < config('dentissa.asistente_agenda.duracion_larga_minutos')) {
            return 0;
        }

        return config('dentissa.asistente_agenda.margen_minutos');
    }
}


<?php

namespace App\Services;

use App\Enums\EstadoCita;
use App\Models\BloqueoAgenda;
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
        // Días u horas bloqueados (vacaciones, imprevistos), 24/11/2026.
        $bloqueos = BloqueoAgenda::tramosDelDia($fecha);

        $horarios = [];
        $candidato = $apertura->copy();

        // La cita debe TERMINAR antes del cierre; el margen de limpieza solo cuenta
        // entre una cita y la siguiente (tieneCupo), no después de la última del día
        // (22/11/2026: antes una extracción de 60 min terminaba a las 16:30 y no a las 17:00).
        while ($candidato->copy()->addMinutes($duracionMinutos)->lte($cierre)) {
            if ($candidato->isFuture()
                && ! BloqueoAgenda::chocaCon($bloqueos, $candidato, $candidato->copy()->addMinutes($duracionMinutos))
                && $this->tieneCupo($candidato, $duracionMinutos, $margenMinutos, $citasDelDia, $horario->capacidad_simultanea)) {
                $horarios[] = $candidato->format('H:i');
            }

            $candidato->addMinutes($intervalo);
        }

        return $horarios;
    }

    /**
     * Resume un mes completo para el calendario de la agenda: por cada día,
     * cuántos horarios libres hay y, si no hay ninguno, por qué.
     *
     * Estados: "pasado" (antes de hoy), "cerrado" (el consultorio no abre ese
     * día de la semana), "sin_horarios" (abre, pero ya no queda cupo) y
     * "disponible". Reutiliza horariosDisponibles(), así el número que ve el
     * paciente en el calendario es exactamente la lista que verá al elegir el día.
     *
     * @return array<string, array{horarios: int, estado: string}>
     */
    public function resumenMensual(CarbonImmutable $mes, Tratamiento $tratamiento, ?int $excludeCitaId = null): array
    {
        $hoy = CarbonImmutable::today();
        $horariosSemana = HorarioAtencion::all()->keyBy('dia_semana');
        // Días bloqueados completos del mes: se ven como cerrados (24/11/2026).
        $bloqueosDelMes = BloqueoAgenda::whereDate('fecha_inicio', '<=', $mes->endOfMonth()->toDateString())
            ->whereDate('fecha_fin', '>=', $mes->startOfMonth()->toDateString())
            ->get()
            ->filter(fn (BloqueoAgenda $bloqueo) => $bloqueo->todoElDia());
        $dias = [];

        for ($dia = $mes->startOfMonth(); $dia->isSameMonth($mes); $dia = $dia->addDay()) {
            $fecha = $dia->toDateString();
            $horario = $horariosSemana->get($dia->dayOfWeek);

            if ($dia->lt($hoy)) {
                $dias[$fecha] = ['horarios' => 0, 'estado' => 'pasado'];
            } elseif (! $horario || ! $horario->abierto
                || $bloqueosDelMes->contains(fn (BloqueoAgenda $bloqueo) => $bloqueo->fecha_inicio->toDateString() <= $fecha && $bloqueo->fecha_fin->toDateString() >= $fecha)) {
                $dias[$fecha] = ['horarios' => 0, 'estado' => 'cerrado'];
            } else {
                $libres = count($this->horariosDisponibles($fecha, $tratamiento, $excludeCitaId));
                $dias[$fecha] = ['horarios' => $libres, 'estado' => $libres > 0 ? 'disponible' : 'sin_horarios'];
            }
        }

        return $dias;
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

        // 22/11/2026: antes solo se revisaba el cupo y el servidor aceptaba citas a
        // las 22:00 o que terminaban después del cierre. Misma regla que la lista
        // que ven los pacientes: dentro del horario, terminando antes del cierre y
        // en los saltos de 30 min contados desde la apertura.
        if (! $this->dentroDelHorario(Carbon::parse($fechaHora), $horario, $this->duracionMinutos($tratamiento))) {
            return false;
        }

        // El servidor también respeta los bloqueos, aunque la petición no venga del calendario.
        $inicio = Carbon::parse($fechaHora);
        if (BloqueoAgenda::chocaCon(BloqueoAgenda::tramosDelDia($inicio->toDateString()), $inicio, $inicio->copy()->addMinutes($this->duracionMinutos($tratamiento)))) {
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

    private function dentroDelHorario(Carbon $inicio, HorarioAtencion $horario, int $duracionMinutos): bool
    {
        $apertura = $inicio->copy()->setTime($horario->hora_apertura, 0);
        $cierre = $inicio->copy()->setTime($horario->hora_cierre, 0);
        $desdeApertura = (int) $apertura->diffInMinutes($inicio, false);

        return $inicio->gte($apertura)
            && $inicio->copy()->addMinutes($duracionMinutos)->lte($cierre)
            && $desdeApertura % max((int) config('dentissa.intervalo_minutos'), 1) === 0
            && (int) $inicio->format('s') === 0;
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

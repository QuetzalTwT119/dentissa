<?php

namespace App\Console\Commands;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Services\NotificacionService;
use App\Support\MomentoRecordatorio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Manda el recordatorio el día hábil anterior a la cita, una hora después de
 * abrir (24/11/2026; antes, exactamente 24 h antes). El momento lo calcula
 * MomentoRecordatorio con Horarios de atención y los bloqueos. El scheduler lo
 * corre cada 15 minutos (routes/console.php): sale en la primera vuelta que ya
 * pasó ese momento.
 */
class EnviarRecordatoriosCitas extends Command
{
    /** Un lunes puede recordarse el viernes (o antes, si hay días cerrados). */
    public const DIAS_ADELANTE = 8;

    protected $signature = 'dentissa:enviar-recordatorios';

    protected $description = 'Envía por WhatsApp y correo el recordatorio de las citas, el día hábil anterior';

    public function handle(NotificacionService $notificaciones): int
    {
        $ahora = now();
        $limite = $ahora->copy()->addDays(self::DIAS_ADELANTE);
        $horarios = HorarioAtencion::all()->keyBy('dia_semana');

        $citas = Cita::query()
            ->whereIn('estado', [EstadoCita::Agendada, EstadoCita::Confirmada])
            ->whereNull('recordatorio_enviado_en')
            ->where('fecha_hora', '>', $ahora)
            ->where('fecha_hora', '<=', $limite)
            ->with(['paciente', 'tratamiento'])
            ->orderBy('fecha_hora')
            ->get()
            ->filter(function (Cita $cita) use ($ahora, $horarios) {
                $momento = MomentoRecordatorio::para($cita->fecha_hora, $horarios);

                // Aún no le toca; o se agendó después de ese momento: el paciente
                // acaba de recibir su confirmación y no necesita otro mensaje.
                return $momento->lte($ahora) && $cita->created_at->lte($momento);
            });

        $enviados = 0;

        foreach ($citas as $cita) {
            // Reclama la cita con un UPDATE condicional: si dos ejecuciones se
            // cruzan, solo una cambia la fila y solo esa manda el aviso.
            $reclamada = Cita::query()
                ->whereKey($cita->id)
                ->whereNull('recordatorio_enviado_en')
                ->update(['recordatorio_enviado_en' => now()]);

            if ($reclamada === 0) {
                continue;
            }

            try {
                $notificaciones->citaRecordatorio($cita);
                $enviados++;
            } catch (Throwable $e) {
                // Una cita con datos raros no debe impedir avisar a las demás.
                Log::warning('No se pudo enviar el recordatorio de una cita', [
                    'id_cita' => $cita->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Recordatorios enviados: {$enviados}.");

        return self::SUCCESS;
    }
}

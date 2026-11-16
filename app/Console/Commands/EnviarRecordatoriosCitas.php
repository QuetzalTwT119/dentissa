<?php

namespace App\Console\Commands;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Services\NotificacionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Manda el recordatorio a las citas que empiezan dentro de las próximas 24 h.
 * El scheduler lo corre cada 15 minutos (routes/console.php), así que cada
 * cita recibe su aviso entre 24 h y 23 h 45 min antes.
 */
class EnviarRecordatoriosCitas extends Command
{
    public const HORAS_ANTES = 24;

    protected $signature = 'dentissa:enviar-recordatorios';

    protected $description = 'Envía por WhatsApp y correo el recordatorio de las citas de las próximas 24 horas';

    public function handle(NotificacionService $notificaciones): int
    {
        $ahora = now();
        $limite = $ahora->copy()->addHours(self::HORAS_ANTES);

        $citas = Cita::query()
            ->whereIn('estado', [EstadoCita::Agendada, EstadoCita::Confirmada])
            ->whereNull('recordatorio_enviado_en')
            ->where('fecha_hora', '>', $ahora)
            ->where('fecha_hora', '<=', $limite)
            ->with(['paciente', 'tratamiento'])
            ->orderBy('fecha_hora')
            ->get()
            // Agendada con menos de 24 h de anticipación: el paciente acaba de
            // recibir su confirmación. Se filtra en PHP porque restar horas a
            // una columna se escribe distinto en SQLite y en MySQL.
            ->filter(fn (Cita $cita) => $cita->created_at->lte($cita->fecha_hora->copy()->subHours(self::HORAS_ANTES)));

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

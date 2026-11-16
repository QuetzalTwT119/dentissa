<?php

namespace App\Services;

use App\Enums\EstadoCita;
use App\Models\Cita;
use Illuminate\Support\Facades\DB;

/**
 * Aplica la respuesta del paciente al recordatorio de su cita.
 *
 * Es el único lugar con estas reglas: hoy lo usan los enlaces firmados del
 * correo y, en la etapa 2, lo usará el webhook de los botones de WhatsApp.
 * Así los dos canales no pueden comportarse distinto.
 */
class RespuestaRecordatorioService
{
    public const CONFIRMAR = 'confirmar';

    public const CANCELAR = 'cancelar';

    public const REAGENDAR = 'reagendar';

    public const ACCIONES = [self::CONFIRMAR, self::CANCELAR, self::REAGENDAR];

    /** La cita ya pasó, se canceló o se atendió: la respuesta ya no aplica. */
    public const NO_DISPONIBLE = 'no_disponible';

    public const MOTIVO_CANCELAR = 'Cancelada por el paciente desde el recordatorio.';

    public const MOTIVO_REAGENDAR = 'El paciente pidió reagendar desde el recordatorio; el horario quedó libre.';

    public function __construct(private NotificacionInternaService $notificacionesInternas) {}

    /** ¿La cita todavía admite una respuesta del paciente? */
    public function admiteRespuesta(Cita $cita): bool
    {
        return in_array($cita->estado, [EstadoCita::Agendada, EstadoCita::Confirmada], true)
            && $cita->fecha_hora->isFuture();
    }

    /**
     * Devuelve la acción aplicada o NO_DISPONIBLE. Repetir "confirmar" es
     * inofensivo: el paciente puede pulsar el botón dos veces.
     */
    public function responder(Cita $cita, string $accion): string
    {
        $resultado = DB::transaction(function () use ($cita, $accion) {
            // Bloquea la fila: si el personal cancela al mismo tiempo, una de
            // las dos operaciones ve el estado ya cambiado y no lo pisa.
            $cita = Cita::query()->lockForUpdate()->findOrFail($cita->id);

            if (! $this->admiteRespuesta($cita)) {
                return [self::NO_DISPONIBLE, $cita, false];
            }

            // Un segundo "Asistiré" no vuelve a avisar al personal.
            $avisar = ! ($accion === self::CONFIRMAR && $cita->asistencia_confirmada_en);

            match ($accion) {
                self::CONFIRMAR => $cita->update([
                    'estado' => EstadoCita::Confirmada,
                    'fecha_confirmacion' => $cita->fecha_confirmacion ?? now(),
                    'asistencia_confirmada_en' => $cita->asistencia_confirmada_en ?? now(),
                ]),
                self::CANCELAR, self::REAGENDAR => $cita->update([
                    'estado' => EstadoCita::Cancelada,
                    'cancelada_por' => null,
                    'fecha_cancelacion' => now(),
                    'motivo_cancelacion' => $accion === self::CANCELAR ? self::MOTIVO_CANCELAR : self::MOTIVO_REAGENDAR,
                ]),
            };

            return [$accion, $cita, $avisar];
        });

        [$aplicada, $cita, $avisar] = $resultado;

        // Fuera de la transacción: un aviso al personal nunca debe deshacer
        // la respuesta del paciente.
        $evento = match ($aplicada) {
            self::CONFIRMAR => 'asistencia_confirmada',
            self::CANCELAR => 'cancelada_paciente',
            self::REAGENDAR => 'reagendar_paciente',
            default => null,
        };

        if ($evento && $avisar) {
            $this->notificacionesInternas->cambioCita($cita, $evento);
        }

        return $aplicada;
    }
}

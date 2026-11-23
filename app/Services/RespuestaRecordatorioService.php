<?php

namespace App\Services;

use App\Enums\EstadoCita;
use App\Models\Cita;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

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

    /** Días que dura el link para elegir otro horario. */
    public const DIAS_ENLACE_REAGENDAR = 7;

    /**
     * Link a la agenda en línea que abre directo en el calendario, con el mismo
     * tratamiento y los datos del paciente ya llenos (23/11/2026). Va firmado y
     * caduca: nadie puede cambiar el número de cita para ver datos de otro.
     */
    /**
     * Viaja oculto en el formulario de la agenda en línea: al guardar, la cita
     * nueva se conecta con la vieja y el paciente recibe "tu cita se movió".
     */
    public static function tokenReagendar(Cita $cita): string
    {
        return Crypt::encryptString(json_encode([
            'cita' => $cita->id,
            'hasta' => now()->addDays(self::DIAS_ENLACE_REAGENDAR)->timestamp,
        ]));
    }

    /**
     * Cita vieja que se puede conectar: token válido y vigente, cancelada porque
     * el paciente pidió reagendar, todavía sin cita nueva y del mismo celular.
     */
    public static function citaAReagendar(?string $token, string $telefono): ?Cita
    {
        if (blank($token)) {
            return null;
        }

        try {
            $datos = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($datos) || ($datos['hasta'] ?? 0) < now()->timestamp) {
            return null;
        }

        $cita = Cita::with('paciente')->find((int) ($datos['cita'] ?? 0));

        $mismoCelular = fn (?string $a) => preg_replace('/\D/', '', (string) $a) === preg_replace('/\D/', '', $telefono);

        return $cita
            && $cita->estado === EstadoCita::Cancelada
            && $cita->motivo_cancelacion === self::MOTIVO_REAGENDAR
            && $cita->id_cita_nueva === null
            && $mismoCelular($cita->paciente?->telefono)
            ? $cita : null;
    }

    public static function enlaceReagendar(Cita $cita): string
    {
        return URL::temporarySignedRoute(
            'citas.publica.create',
            now()->addDays(self::DIAS_ENLACE_REAGENDAR),
            ['reagendar' => $cita->id],
        );
    }

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

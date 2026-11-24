<?php

namespace App\Services;

use App\Support\Permisos;
use App\Enums\RolUsuario;
use App\Events\NotificacionSistemaCreada;
use App\Models\Cita;
use App\Models\NotificacionSistema;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificacionInternaService
{
    /**
     * Aviso del sistema solo para administradores, en la campana y por correo
     * (por ejemplo, un respaldo fallido). Nunca lanza: si avisar también
     * falla, queda en el log.
     */
    public function alertaSistema(string $evento, string $titulo, string $mensaje): void
    {
        // Alertas del sistema: a quien administra la configuración (antes "admin").
        $admins = User::query()->where('activo', true)->whereIn('rol', Permisos::valoresCon('configuracion'))->get(['id', 'email']);

        foreach ($admins as $admin) {
            try {
                $notificacion = NotificacionSistema::create([
                    'user_id' => $admin->id,
                    'evento' => $evento,
                    'titulo' => $titulo,
                    'mensaje' => $mensaje,
                    'url' => route('notificaciones.index', [], false),
                ]);
                $this->transmitir($notificacion);
            } catch (Throwable $e) {
                Log::warning('No se pudo guardar una alerta del sistema', ['evento' => $evento, 'error' => $e->getMessage()]);
            }

            try {
                Mail::raw($mensaje, fn ($correo) => $correo->to($admin->email)->subject("Dentissa: {$titulo}"));
            } catch (Throwable $e) {
                Log::warning('No se pudo enviar por correo una alerta del sistema', ['evento' => $evento, 'error' => $e->getMessage()]);
            }
        }
    }

    public function cambioCita(Cita $cita, string $evento, ?int $actorId = null): void
    {
        $etiquetas = [
            'agendada' => ['Nueva cita registrada', 'Se registró una cita para'],
            'reagendada' => ['Cita reagendada', 'Se cambió la fecha de una cita para'],
            'confirmada' => ['Cita confirmada', 'Se confirmó una cita para'],
            'cancelada' => ['Cita cancelada', 'Se canceló una cita que estaba programada para'],
            'iniciada' => ['Atención iniciada', 'Comenzó la atención de una cita programada para'],
            'no_asistio' => ['Inasistencia registrada', 'Se registró una inasistencia para la cita de'],
            'completada' => ['Cita completada', 'Se completó una cita programada para'],
            // Respuestas del paciente al recordatorio (RespuestaRecordatorioService).
            'asistencia_confirmada' => ['Paciente confirmó asistencia', 'El paciente confirmó que asistirá a su cita del'],
            'cancelada_paciente' => ['Paciente canceló su cita', 'El paciente canceló desde el recordatorio su cita del'],
            'reagendar_paciente' => ['Paciente pidió reagendar', 'El paciente pidió reagendar y liberó el horario del'],
            'reagendada_paciente' => ['Paciente eligió su nuevo horario', 'El paciente reagendó su cita desde el recordatorio; su nuevo horario es el'],
        ];

        if (! isset($etiquetas[$evento])) {
            return;
        }

        [$titulo, $prefijo] = $etiquetas[$evento];
        $fecha = $cita->fecha_hora->translatedFormat('d M Y, H:i');
        // Cita agendada con una oferta (21/11/2026): la doctora la aplica al cobrar.
        $oferta = $cita->id_oferta ? $cita->oferta : null;
        $conOferta = $oferta ? " Con oferta «{$oferta->titulo}» ({$oferta->etiquetaBeneficio()})." : '';
        $destinatarios = User::query()
            ->where('activo', true)
            // Avisos de citas: a quien puede ver notificaciones (config/permisos.php).
            ->whereIn('rol', Permisos::valoresCon('notificaciones'))
            ->when($actorId, fn ($query) => $query->whereKeyNot($actorId))
            ->pluck('id');

        // 25/11/2026: un error con una persona ya no deja sin aviso a las demás.
        foreach ($destinatarios as $userId) {
            try {
                $notificacion = NotificacionSistema::create([
                    'user_id' => $userId,
                    'id_cita' => $cita->id,
                    'evento' => $evento,
                    'titulo' => $titulo,
                    'mensaje' => "{$prefijo} {$fecha}.{$conOferta}",
                    // La ruta relativa conserva la sesión aunque se acceda por localhost,
                    // IP local o un dominio de producción distinto.
                    'url' => route('citas.index', [], false),
                ]);
            } catch (Throwable $exception) {
                Log::warning('No se pudo guardar una notificación interna de cita', [
                    'id_cita' => $cita->id,
                    'evento' => $evento,
                    'error' => $exception->getMessage(),
                ]);

                continue;
            }

            $this->transmitir($notificacion);
        }
    }

    /**
     * Manda el aviso a la campana en vivo cuando la transacción termina bien.
     *
     * 25/11/2026: CrearCitaService llama a cambioCita() dentro de su transacción.
     * Antes el aviso salía en ese momento; si la cita no llegaba a guardarse (por
     * ejemplo, un doble toque en Telegram), la campana mostraba un aviso que ya no
     * existía. Sin transacción abierta, sale de inmediato.
     */
    private function transmitir(NotificacionSistema $notificacion): void
    {
        DB::afterCommit(function () use ($notificacion): void {
            try {
                event(new NotificacionSistemaCreada($notificacion));
            } catch (Throwable $exception) {
                Log::warning('No se pudo transmitir una notificación interna en tiempo real', [
                    'id_notificacion' => $notificacion->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}

<?php

namespace App\Services;

use App\Enums\CanalNotificacion;
use App\Enums\TipoNotificacion;
use App\Mail\CitaNotificacion;
use App\Mail\RecordatorioCita;
use App\Models\Cita;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Punto único para avisarle a un paciente sobre su cita: intenta WhatsApp
 * primero (plantilla aprobada), cae a correo si no hay teléfono, WhatsApp no
 * está configurado o Meta rechaza el envío, y deja un registro auditable en
 * `notificaciones` sin importar qué canal terminó usándose (o si ninguno).
 *
 * Nunca lanza — un problema de notificación no debe tumbar el flujo de
 * agendar/confirmar/cancelar/reagendar una cita.
 */
class NotificacionService
{
    public function __construct(private WhatsappService $whatsapp) {}

    public function citaAgendada(Cita $cita): void
    {
        $fecha = $this->formatearFecha($cita);
        $tratamiento = $cita->tratamiento?->nombre ?? 'tu tratamiento';

        $this->notificar(
            $cita,
            canal: CanalNotificacion::CitaAgendada,
            plantilla: 'cita_agendada',
            titulo: 'Tu cita quedó agendada',
            mensaje: "Hola {$cita->paciente->nombre}, tu cita de {$tratamiento} quedó agendada para el {$fecha}. El pago se realiza en el consultorio el día de tu cita. Te esperamos.",
            parametrosPlantilla: [$cita->paciente->nombre, $tratamiento, $fecha],
        );
    }

    public function citaConfirmada(Cita $cita): void
    {
        $fecha = $this->formatearFecha($cita);

        $this->notificar(
            $cita,
            canal: CanalNotificacion::CitaConfirmada,
            plantilla: 'cita_confirmada',
            titulo: 'Tu cita fue confirmada',
            mensaje: "Hola {$cita->paciente->nombre}, confirmamos tu cita del {$fecha}. El pago se realiza en el consultorio el día de tu cita. Te esperamos.",
            parametrosPlantilla: [$cita->paciente->nombre, $fecha],
        );
    }

    public function citaCancelada(Cita $cita): void
    {
        $fecha = $this->formatearFecha($cita);
        $motivo = $cita->motivo_cancelacion
            ? " Motivo: {$cita->motivo_cancelacion}."
            : ' Si fue un error, contáctanos para reagendar.';

        $this->notificar(
            $cita,
            canal: CanalNotificacion::CitaCancelada,
            plantilla: 'cita_cancelada',
            titulo: 'Tu cita fue cancelada',
            mensaje: "Hola {$cita->paciente->nombre}, tu cita del {$fecha} fue cancelada.{$motivo}",
            parametrosPlantilla: [$cita->paciente->nombre, $fecha],
        );
    }

    public function citaReagendada(Cita $cita): void
    {
        $fecha = $this->formatearFecha($cita);

        $this->notificar(
            $cita,
            canal: CanalNotificacion::CitaReagendada,
            plantilla: 'cita_reagendada',
            titulo: 'Tu cita fue reagendada',
            mensaje: "Hola {$cita->paciente->nombre}, tu cita se movió al {$fecha}. El pago se realiza en el consultorio el día de tu cita. Te esperamos en el nuevo horario.",
            parametrosPlantilla: [$cita->paciente->nombre, $fecha],
        );
    }

    /**
     * Recordatorio 24 h antes. A diferencia de los demás avisos, no es una
     * cascada: se intenta WhatsApp Y correo, cada uno por su lado, y cada
     * envío exitoso deja su propia fila en `notificaciones`. Los dos traen las
     * opciones Asistiré / Cancelar / Reagendar.
     */
    public function citaRecordatorio(Cita $cita): void
    {
        $paciente = $cita->paciente;

        if (! $paciente) {
            return;
        }

        $fecha = $this->formatearFecha($cita);
        $tratamiento = $cita->tratamiento?->nombre ?? 'tu tratamiento';
        $titulo = 'Recordatorio de tu cita';
        $mensaje = "Hola {$paciente->nombre}, te recordamos tu cita de {$tratamiento} el {$fecha}. ¿Nos confirmas si vas a asistir?";
        $enviados = [];

        if ($paciente->telefono && $this->whatsapp->enviarPlantilla(
            $paciente->telefono,
            'cita_recordatorio',
            [$paciente->nombre, $tratamiento, $fecha],
            array_map(
                fn (string $accion) => "recordatorio:{$cita->id}:{$accion}",
                RespuestaRecordatorioService::ACCIONES,
            ),
        )) {
            $enviados[] = TipoNotificacion::Whatsapp;
        }

        if ($paciente->email) {
            try {
                Mail::to($paciente->email)->send(new RecordatorioCita($cita, $titulo, $mensaje));
                $enviados[] = TipoNotificacion::Email;
            } catch (Throwable $e) {
                Log::warning('No se pudo enviar el correo de recordatorio de la cita', [
                    'id_cita' => $cita->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Si ningún canal funcionó queda una fila sin tipo, igual que en los
        // demás avisos, para que el intento fallido sea auditable.
        foreach ($enviados ?: [null] as $tipo) {
            Notificacion::create([
                'id_paciente' => $paciente->id,
                'id_cita' => $cita->id,
                'tipo' => $tipo,
                'canal' => CanalNotificacion::CitaRecordatorio,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
            ]);
        }
    }

    private function formatearFecha(Cita $cita): string
    {
        // Mismo formato que ya usa resources/views/citas/index.blade.php, pero
        // siempre en español: APP_LOCALE es "en" y el paciente leía "Jan".
        return $cita->fecha_hora->copy()->locale('es')->translatedFormat('d M Y, H:i');
    }

    private function notificar(
        Cita $cita,
        CanalNotificacion $canal,
        string $plantilla,
        string $titulo,
        string $mensaje,
        array $parametrosPlantilla,
    ): void {
        $paciente = $cita->paciente;

        if (! $paciente) {
            return;
        }

        $tipoUsado = null;

        if ($paciente->telefono && $this->whatsapp->enviarPlantilla($paciente->telefono, $plantilla, $parametrosPlantilla)) {
            $tipoUsado = TipoNotificacion::Whatsapp;
        } elseif ($paciente->email) {
            try {
                Mail::to($paciente->email)->send(new CitaNotificacion($titulo, $mensaje));
                $tipoUsado = TipoNotificacion::Email;
            } catch (Throwable $e) {
                Log::warning('No se pudo enviar el correo de respaldo de la cita', [
                    'id_cita' => $cita->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Notificacion::create([
            'id_paciente' => $paciente->id,
            'id_cita' => $cita->id,
            'tipo' => $tipoUsado,
            'canal' => $canal,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
        ]);
    }
}

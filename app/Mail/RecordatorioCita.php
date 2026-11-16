<?php

namespace App\Mail;

use App\Models\Cita;
use App\Services\RespuestaRecordatorioService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Recordatorio 24 h antes de la cita, con un enlace firmado por cada
 * respuesta posible. La firma impide cambiar el número de cita o la acción en
 * la URL; después de la hora de la cita la respuesta ya no se acepta.
 */
class RecordatorioCita extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Cita $cita,
        public string $titulo,
        public string $mensaje,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->titulo);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio-cita',
            with: ['enlaces' => self::enlaces($this->cita)],
        );
    }

    /** @return array<string, string> acción => URL firmada */
    public static function enlaces(Cita $cita): array
    {
        $enlaces = [];

        foreach (RespuestaRecordatorioService::ACCIONES as $accion) {
            $enlaces[$accion] = self::urlFirmada('citas.recordatorio.show', $cita, $accion);
        }

        return $enlaces;
    }

    /**
     * La firma dura un día más que la cita: después de la hora el enlace
     * todavía abre y explica que ya no se puede responder, en lugar de un 403.
     * Que ya no se pueda responder lo decide RespuestaRecordatorioService.
     */
    public static function urlFirmada(string $ruta, Cita $cita, string $accion): string
    {
        return URL::temporarySignedRoute(
            $ruta,
            $cita->fecha_hora->copy()->addDay(),
            ['cita' => $cita->id, 'accion' => $accion],
        );
    }
}

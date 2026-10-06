<?php

namespace App\Console\Commands;

use App\Services\AsistentePacienteService;
use App\Services\AgendaTelegramService;
use App\Services\AtencionOportunaService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ProcesarMensajesTelegram extends Command
{
    protected $signature = 'dentissa:telegram {--once : Procesa los mensajes recibidos una sola vez}';

    protected $description = 'Responde mensajes privados de Telegram con información aprobada de Dentissa';

    public function handle(TelegramService $telegram, AsistentePacienteService $asistente, AgendaTelegramService $agenda, AtencionOportunaService $atencion): int
    {
        if (! $telegram->configurado()) {
            $this->error('Falta DENTISSA_TELEGRAM_BOT_TOKEN en el archivo .env local.');

            return self::FAILURE;
        }

        do {
            $offset = Cache::get('dentissa.telegram.offset');

            try {
                $updates = $telegram->updates($offset, $this->option('once') ? 0 : 25);
            } catch (Throwable $e) {
                $this->error('No fue posible consultar Telegram. Revisa el token y tu conexión.');

                return self::FAILURE;
            }

            foreach ($updates as $update) {
                $this->procesarUpdate($update, $telegram, $asistente, $agenda, $atencion);

                if (isset($update['update_id'])) {
                    Cache::forever('dentissa.telegram.offset', ((int) $update['update_id']) + 1);
                }
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $update */
    private function procesarUpdate(array $update, TelegramService $telegram, AsistentePacienteService $asistente, AgendaTelegramService $agenda, AtencionOportunaService $atencion): void
    {
        $mensaje = $update['message'] ?? null;

        if (! is_array($mensaje) || ($mensaje['chat']['type'] ?? null) !== 'private') {
            return;
        }

        $chatId = $mensaje['chat']['id'] ?? null;
        $texto = trim((string) ($mensaje['text'] ?? ''));

        if (! $chatId || $texto === '') {
            return;
        }

        if (str_starts_with($texto, '/start')) {
            $respuesta = 'Hola. Soy el asistente de Dentissa. Puedo responder dudas con la información aprobada del consultorio y orientarte para agendar una cita.';
        } else {
            $consulta = $asistente->responder($texto);
            $orientacion = $agenda->tieneConsultaEnCurso($chatId) ? null : $atencion->responder($texto);
            $respuesta = $orientacion
                ? $orientacion['respuesta']."\n\n".$orientacion['accion']['url']
                : ((! $agenda->tieneConsultaEnCurso($chatId) && $consulta['encontrada'])
                    ? $consulta['respuesta']
                    : ($agenda->responder($chatId, $texto) ?? $consulta['respuesta']));
        }

        $telegram->enviarMensaje($chatId, $respuesta);
    }
}

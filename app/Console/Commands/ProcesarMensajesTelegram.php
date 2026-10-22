<?php

namespace App\Console\Commands;

use App\Services\AgendaTelegramService;
use App\Services\AsistentePacienteService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcesarMensajesTelegram extends Command
{
    protected $signature = 'dentissa:telegram {--once : Procesa los mensajes recibidos una sola vez}';

    protected $description = 'Responde mensajes privados de Telegram con información aprobada de Dentissa';

    public function handle(TelegramService $telegram, AsistentePacienteService $asistente, AgendaTelegramService $agenda): int
    {
        if (! $telegram->configurado()) {
            $this->error('Falta DENTISSA_TELEGRAM_BOT_TOKEN en el archivo .env local.');

            return self::FAILURE;
        }

        try {
            $telegram->configurarComandos();
        } catch (Throwable $e) {
            // Command menu failure must not prevent the bot from answering messages.
            Log::warning('No se pudo actualizar el menú de comandos de Telegram; el bot continuará.', [
                'error' => $e->getMessage(),
            ]);
        }

        do {
            $offset = Cache::get('dentissa.telegram.offset');

            try {
                $updates = $telegram->updates($offset, $this->option('once') ? 0 : 25);
            } catch (Throwable $e) {
                if ($this->option('once')) {
                    $this->error('No fue posible consultar Telegram. Revisa el token y tu conexión.');

                    return self::FAILURE;
                }

                Log::warning('Falló la consulta de actualizaciones de Telegram; se reintentará.', [
                    'error' => $e->getMessage(),
                ]);
                sleep(5);

                continue;
            }

            foreach ($updates as $update) {
                try {
                    $this->procesarUpdate($update, $telegram, $asistente, $agenda);
                } catch (Throwable $e) {
                    Log::warning('No se pudo procesar una actualización de Telegram; se reintentará.', [
                        'update_id' => $update['update_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);

                    // Do not advance the cursor: Telegram will redeliver this update for retry.
                    if (! $this->option('once')) {
                        sleep(2);
                    }

                    break;
                }

                if (isset($update['update_id'])) {
                    Cache::forever('dentissa.telegram.offset', ((int) $update['update_id']) + 1);
                }
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $update */
    private function procesarUpdate(array $update, TelegramService $telegram, AsistentePacienteService $asistente, AgendaTelegramService $agenda): void
    {
        $callback = $update['callback_query'] ?? null;

        if (is_array($callback)) {
            $chatId = $callback['message']['chat']['id'] ?? null;
            $callbackQueryId = $callback['id'] ?? null;

            if (! $chatId || ! $callbackQueryId) {
                return;
            }

            try {
                $telegram->responderCallback((string) $callbackQueryId);
            } catch (Throwable $e) {
                // Telegram expires callback queries quickly; still try to process the queued selection.
                Log::notice('Telegram ya no aceptó la confirmación visual de un botón.', [
                    'callback_query_id' => $callbackQueryId,
                    'error' => $e->getMessage(),
                ]);
            }

            $callbackData = (string) ($callback['data'] ?? '');

            if ($callbackData === 'agenda:sin-accion') {
                return;
            }

            $respuesta = $agenda->responderSeleccion($chatId, $callbackData, (string) $callbackQueryId);

            if (str_starts_with($callbackData, 'agenda:calendario:')
                && isset($callback['message']['message_id'])) {
                try {
                    $telegram->actualizarMensaje(
                        $chatId,
                        $callback['message']['message_id'],
                        $respuesta,
                        $agenda->tecladoActual($chatId),
                    );
                } catch (Throwable $e) {
                    Log::notice('No se pudo actualizar el calendario original de Telegram; se enviará uno nuevo.', [
                        'message_id' => $callback['message']['message_id'],
                        'error' => $e->getMessage(),
                    ]);
                    $telegram->enviarMensaje($chatId, $respuesta, $agenda->tecladoActual($chatId));
                }

                return;
            }

            if (isset($callback['message']['message_id'])) {
                try {
                    $telegram->quitarBotones($chatId, $callback['message']['message_id']);
                } catch (Throwable $e) {
                    // The button can belong to an old/deleted message; the new prompt is still useful.
                    Log::notice('No se pudieron quitar botones de un mensaje anterior de Telegram.', [
                        'message_id' => $callback['message']['message_id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            $telegram->enviarMensaje($chatId, $respuesta, $agenda->tecladoActual($chatId));

            return;
        }

        $mensaje = $update['message'] ?? null;

        if (! is_array($mensaje) || ($mensaje['chat']['type'] ?? null) !== 'private') {
            return;
        }

        $chatId = $mensaje['chat']['id'] ?? null;

        if (! $chatId) {
            return;
        }

        $contacto = $mensaje['contact'] ?? null;
        $texto = trim((string) ($mensaje['text'] ?? ''));

        if (is_array($contacto)) {
            if (! $agenda->esperaTelefono($chatId)) {
                $telegram->enviarMensaje($chatId, 'Te pediré compartir el teléfono solo durante el paso de contacto. Escribe /agendar para comenzar una cita.');

                return;
            }

            $idUsuario = $mensaje['from']['id'] ?? null;
            $idContacto = $contacto['user_id'] ?? null;

            if (! $idUsuario || ! $idContacto || (string) $idUsuario !== (string) $idContacto) {
                $telegram->enviarMensaje(
                    $chatId,
                    'Por privacidad, comparte tu propio número con el botón o escríbelo manualmente.',
                    $agenda->tecladoActual($chatId),
                );

                return;
            }

            $texto = trim((string) ($contacto['phone_number'] ?? ''));
        }

        if ($texto === '') {
            return;
        }

        $esAyuda = (bool) preg_match('/^\/(?:start|ayuda|help)(?:@\w+)?(?:\s|$)/iu', $texto);

        if ($esAyuda) {
            if (preg_match('/^\/start(?:@\w+)?(?:\s|$)/iu', $texto)) {
                // /start means a fresh conversation; discard only an unfinished booking draft.
                $agenda->responder($chatId, '/cancelar');
            }

            $respuesta = "¡Hola! 👋 Soy el asistente virtual de Dentissa.\n\n"
                ."Puedo ayudarte con información del consultorio y a reservar una cita aquí mismo.\n\n"
                ."• Para comenzar una reserva, toca /agendar.\n"
                ."• Puedes escribir /cancelar si cambias de opinión.\n"
                ."• También puedes preguntarme sobre nuestros servicios.\n\n"
                .'Te guiaré paso a paso.';
        } elseif (preg_match('/^\/agendar(?:@\w+)?(?:\s|$)/iu', $texto)) {
            $respuesta = $agenda->iniciar($chatId);
        } else {
            $agendaEnCurso = $agenda->tieneConsultaEnCurso($chatId);
            $consulta = $asistente->responder($texto);
            $respuesta = (! $agendaEnCurso && ($consulta['encontrada'] || ($consulta['ambigua'] ?? false)))
                ? $consulta['respuesta']
                : ($agenda->responder($chatId, $texto) ?? $consulta['respuesta']);
        }

        $esReinicio = (bool) preg_match('/^\/start(?:@\w+)?(?:\s|$)/iu', $texto);
        $replyMarkup = ($esAyuda && ! $esReinicio)
            ? null
            : ($esReinicio || mb_strtolower($texto) === '/cancelar'
                ? ['remove_keyboard' => true]
                : $agenda->tecladoActual($chatId));

        $telegram->enviarMensaje($chatId, $respuesta, $replyMarkup);
    }
}

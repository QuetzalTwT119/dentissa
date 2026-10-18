<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class TelegramService
{
    public function __construct(private readonly HttpFactory $http) {}

    public function configurado(): bool
    {
        return filled(config('services.telegram.bot_token'));
    }

    public function configurarComandos(): void
    {
        $respuesta = $this->cliente()->post('/setMyCommands', [
            'commands' => [
                ['command' => 'start', 'description' => 'Iniciar o reiniciar la conversación'],
                ['command' => 'agendar', 'description' => 'Reservar una cita'],
                ['command' => 'cancelar', 'description' => 'Cancelar la cita en curso'],
                ['command' => 'ayuda', 'description' => 'Ver cómo usar el asistente'],
            ],
        ]);

        if (! ($respuesta->json('ok') ?? false)) {
            throw new RuntimeException($this->descripcionError($respuesta->json(), 'configurar comandos', $respuesta->status()));
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function updates(?int $offset = null, int $timeout = 0): array
    {
        $respuesta = $this->cliente($timeout + 10)->post('/getUpdates', array_filter([
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['message', 'callback_query'],
        ], fn ($valor) => $valor !== null));

        return $this->resultado($respuesta->json(), 'consultar actualizaciones', $respuesta->status());
    }

    /** @param array<string, mixed>|null $replyMarkup */
    public function enviarMensaje(int|string $chatId, string $texto, ?array $replyMarkup = null): void
    {
        $datos = [
            'chat_id' => $chatId,
            'text' => $texto,
        ];

        if ($replyMarkup !== null) {
            $datos['reply_markup'] = $replyMarkup;
        }

        $respuesta = $this->cliente()->post('/sendMessage', $datos);

        $this->resultado($respuesta->json(), 'enviar mensaje', $respuesta->status());
    }

    public function responderCallback(string $callbackQueryId, ?string $texto = null): void
    {
        $respuesta = $this->cliente()->post('/answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $texto,
        ], fn ($valor) => $valor !== null));

        if (! ($respuesta->json('ok') ?? false)) {
            throw new RuntimeException($this->descripcionError($respuesta->json(), 'confirmar selección', $respuesta->status()));
        }
    }

    public function quitarBotones(int|string $chatId, int|string $messageId): void
    {
        $respuesta = $this->cliente()->post('/editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => ['inline_keyboard' => []],
        ]);

        if (! ($respuesta->json('ok') ?? false)) {
            throw new RuntimeException($this->descripcionError($respuesta->json(), 'actualizar botones', $respuesta->status()));
        }
    }

    /** @param array<string, mixed>|null $replyMarkup */
    public function actualizarMensaje(int|string $chatId, int|string $messageId, string $texto, ?array $replyMarkup = null): void
    {
        $datos = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $texto,
        ];

        if ($replyMarkup !== null) {
            $datos['reply_markup'] = $replyMarkup;
        }

        $respuesta = $this->cliente()->post('/editMessageText', $datos);

        if (! ($respuesta->json('ok') ?? false)) {
            throw new RuntimeException($this->descripcionError($respuesta->json(), 'actualizar mensaje', $respuesta->status()));
        }
    }

    private function cliente(int $timeout = 10)
    {
        $token = config('services.telegram.bot_token');

        if (blank($token)) {
            throw new RuntimeException('Configura DENTISSA_TELEGRAM_BOT_TOKEN antes de iniciar el bot.');
        }

        return $this->http->baseUrl("https://api.telegram.org/bot{$token}")->timeout($timeout);
    }

    /** @param array<string, mixed>|null $cuerpo */
    private function resultado(?array $cuerpo, string $operacion, int $status): array
    {
        if (! $cuerpo || ! ($cuerpo['ok'] ?? false)) {
            throw new RuntimeException($this->descripcionError($cuerpo, $operacion, $status));
        }

        return $cuerpo['result'] ?? [];
    }

    /** @param array<string, mixed>|null $cuerpo */
    private function descripcionError(?array $cuerpo, string $operacion, int $status): string
    {
        $descripcion = $cuerpo['description'] ?? 'respuesta inválida';

        return "Telegram rechazó {$operacion} (HTTP {$status}): {$descripcion}.";
    }
}

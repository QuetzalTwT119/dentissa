<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class TelegramService
{
    public function __construct(private readonly HttpFactory $http)
    {
    }

    public function configurado(): bool
    {
        return filled(config('services.telegram.bot_token'));
    }

    /** @return array<int, array<string, mixed>> */
    public function updates(?int $offset = null, int $timeout = 0): array
    {
        $respuesta = $this->cliente($timeout + 10)->post('/getUpdates', array_filter([
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => ['message'],
        ], fn ($valor) => $valor !== null));

        return $this->resultado($respuesta->json());
    }

    public function enviarMensaje(int|string $chatId, string $texto): void
    {
        $respuesta = $this->cliente()->post('/sendMessage', [
            'chat_id' => $chatId,
            'text' => $texto,
        ]);

        $this->resultado($respuesta->json());
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
    private function resultado(?array $cuerpo): array
    {
        if (! $cuerpo || ! ($cuerpo['ok'] ?? false)) {
            throw new RuntimeException('Telegram no pudo procesar la solicitud.');
        }

        return $cuerpo['result'] ?? [];
    }
}

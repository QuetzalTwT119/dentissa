<?php

namespace Tests\Feature;

use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    public function test_consulta_updates_y_envia_mensajes_sin_usar_un_token_real(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [['update_id' => 7]],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 3],
            ]),
        ]);

        $telegram = app(TelegramService::class);

        $this->assertSame([['update_id' => 7]], $telegram->updates());
        $telegram->enviarMensaje(123, 'Mensaje de prueba');

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === 123
            && $request['text'] === 'Mensaje de prueba');
    }
}

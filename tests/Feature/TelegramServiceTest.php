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
        $teclado = ['keyboard' => [[['text' => 'Odontología General']]], 'resize_keyboard' => true];
        $telegram->enviarMensaje(123, 'Mensaje de prueba', $teclado);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === 123
            && $request['text'] === 'Mensaje de prueba'
            && $request['reply_markup'] === $teclado);
    }

    public function test_configura_el_menu_de_comandos_en_espanol(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        Http::fake([
            'https://api.telegram.org/*/setMyCommands' => Http::response(['ok' => true, 'result' => true]),
        ]);

        app(TelegramService::class)->configurarComandos();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/setMyCommands')
            && array_column($request['commands'], 'command') === ['start', 'agendar', 'cancelar', 'ayuda']);
    }

    public function test_informa_el_motivo_que_devuelve_telegram_al_fallar_el_envio(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');
        Http::fake([
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => false,
                'description' => 'bot was blocked by the user',
            ], 403),
        ]);

        try {
            app(TelegramService::class)->enviarMensaje(123, 'Mensaje de prueba');
            $this->fail('Se esperaba un error de Telegram.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('HTTP 403', $e->getMessage());
            $this->assertStringContainsString('bot was blocked by the user', $e->getMessage());
        }
    }
}

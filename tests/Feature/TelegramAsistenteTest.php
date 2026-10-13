<?php

namespace Tests\Feature;

use App\Models\PreguntaFrecuente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAsistenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_pide_aclaracion_si_dos_faqs_son_igual_de_probables(): void
    {
        config()->set('services.telegram.bot_token', 'token-de-prueba');

        PreguntaFrecuente::create([
            'pregunta' => '¿Qué horario hay para limpieza dental?',
            'respuesta' => 'Respuesta del horario.',
            'activa' => true,
        ]);
        PreguntaFrecuente::create([
            'pregunta' => '¿Qué costo tiene limpieza dental?',
            'respuesta' => 'Respuesta del costo.',
            'activa' => true,
        ]);

        Http::fake([
            'https://api.telegram.org/*/getUpdates' => Http::response([
                'ok' => true,
                'result' => [[
                    'update_id' => 1,
                    'message' => [
                        'chat' => ['id' => 123, 'type' => 'private'],
                        'text' => '¿Horario o costo de limpieza dental?',
                    ],
                ]],
            ]),
            'https://api.telegram.org/*/sendMessage' => Http::response([
                'ok' => true,
                'result' => ['message_id' => 2],
            ]),
        ]);

        $this->artisan('dentissa:telegram', ['--once' => true])
            ->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === 123
            && str_starts_with($request['text'], 'No pude identificar con seguridad'));
    }
}

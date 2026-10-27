<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GmailApiTransportTest extends TestCase
{
    public function test_gmail_api_transport_accepts_private_environment_credentials(): void
    {
        Cache::forget('dentissa.gmail.access_token');
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-env-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'id' => 'fake-env-message-id',
            ]),
        ]);

        config([
            'services.gmail.client_id' => 'fake-env-client-id',
            'services.gmail.client_secret' => 'fake-env-client-secret',
            'services.gmail.refresh_token' => 'fake-env-refresh-token',
            'mail.from.address' => 'dentissa@example.test',
            'mail.from.name' => 'Dentissa',
        ]);

        Mail::mailer('gmail')->raw('Contenido de prueba con variables', function ($message): void {
            $message->to('destinatario@example.test')
                ->subject('Prueba de variables privadas');
        });

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['client_id'] === 'fake-env-client-id'
            && $request['client_secret'] === 'fake-env-client-secret'
            && $request['refresh_token'] === 'fake-env-refresh-token');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send');
    }

    public function test_gmail_api_transport_refreshes_token_and_sends_mime_message(): void
    {
        Cache::flush();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ]),
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send' => Http::response([
                'id' => 'fake-message-id',
            ]),
        ]);

        $clientFile = tempnam(sys_get_temp_dir(), 'dentissa-oauth-client-');
        $tokenFile = tempnam(sys_get_temp_dir(), 'dentissa-oauth-token-');

        try {
            file_put_contents($clientFile, json_encode([
                'installed' => [
                    'client_id' => 'fake-client-id',
                    'client_secret' => 'fake-client-secret',
                ],
            ], JSON_THROW_ON_ERROR));
            file_put_contents($tokenFile, json_encode([
                'refresh_token' => 'fake-refresh-token',
            ], JSON_THROW_ON_ERROR));

            config([
                'services.gmail.client_file' => $clientFile,
                'services.gmail.token_file' => $tokenFile,
                'mail.from.address' => 'dentissa@example.test',
                'mail.from.name' => 'Dentissa',
            ]);

            Mail::mailer('gmail')->raw('Contenido de prueba', function ($message): void {
                $message->to('destinatario@example.test')
                    ->subject('Prueba de Dentissa');
            });

            Http::assertSent(function (Request $request): bool {
                return $request->url() === 'https://oauth2.googleapis.com/token'
                    && $request['client_id'] === 'fake-client-id'
                    && $request['refresh_token'] === 'fake-refresh-token';
            });

            Http::assertSent(function (Request $request): bool {
                if ($request->url() !== 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send'
                    || $request->hasHeader('Authorization') === false) {
                    return false;
                }

                $encodedMessage = $request['raw'] ?? '';
                $rawMessage = base64_decode(strtr($encodedMessage, '-_', '+/'));

                return str_contains($rawMessage, 'destinatario@example.test')
                    && str_contains($rawMessage, 'Prueba de Dentissa')
                    && str_contains($rawMessage, 'Contenido de prueba');
            });
        } finally {
            @unlink($clientFile);
            @unlink($tokenFile);
        }
    }
}

<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $raw = rtrim(strtr(base64_encode($message->toString()), '+/', '-_'), '=');

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $raw,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gmail API no pudo enviar el mensaje (HTTP '.$response->status().').');
        }
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }

    private function accessToken(): string
    {
        return Cache::remember('dentissa.gmail.access_token', now()->addMinutes(50), function (): string {
            $clientId = config('services.gmail.client_id');
            $clientSecret = config('services.gmail.client_secret');
            $refreshToken = config('services.gmail.refresh_token');
            $configuredSecrets = collect([$clientId, $clientSecret, $refreshToken])
                ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                ->count();

            if ($configuredSecrets > 0 && $configuredSecrets < 3) {
                throw new RuntimeException('Las variables privadas de Gmail OAuth están incompletas.');
            }

            if ($configuredSecrets === 3) {
                $credentials = ['client_id' => $clientId, 'client_secret' => $clientSecret];
            } else {
                $client = $this->readJsonFile(config('services.gmail.client_file'), 'cliente OAuth');
                $token = $this->readJsonFile(config('services.gmail.token_file'), 'token OAuth');
                $credentials = $client['installed'] ?? $client['web'] ?? null;
                $refreshToken = $token['refresh_token'] ?? null;
            }

            if (! is_array($credentials) || empty($credentials['client_id']) || empty($credentials['client_secret']) || empty($refreshToken)) {
                throw new RuntimeException('La configuración privada de Gmail OAuth está incompleta.');
            }

            $response = Http::asForm()->acceptJson()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $credentials['client_id'],
                'client_secret' => $credentials['client_secret'],
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed() || ! $response->json('access_token')) {
                throw new RuntimeException('Google no autorizó el acceso de Gmail. Vuelve a conectar la cuenta emisora.');
            }

            return $response->json('access_token');
        });
    }

    private function readJsonFile(?string $path, string $description): array
    {
        if (! $path || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('No se encontró el archivo privado de '.$description.'.');
        }

        $contents = file_get_contents($path);
        $data = is_string($contents) ? json_decode($contents, true) : null;

        if (! is_array($data)) {
            throw new RuntimeException('El archivo privado de '.$description.' no contiene JSON válido.');
        }

        return $data;
    }
}

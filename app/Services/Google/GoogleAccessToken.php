<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Token de acceso de la cuenta de Google de la clínica, compartido por el
 * correo (Gmail API) y los respaldos (Google Drive). Usa el mismo refresh
 * token: al autorizar con scripts/google-oauth.ps1 se piden ambos permisos.
 *
 * Lee las credenciales de variables de entorno (Railway) o de los archivos
 * privados de storage/app/private (local). Nunca las escribe en logs.
 */
class GoogleAccessToken
{
    /** Se conserva la clave histórica para no invalidar el token en caché. */
    public const CLAVE_CACHE = 'dentissa.gmail.access_token';

    public function obtener(): string
    {
        return Cache::remember(self::CLAVE_CACHE, now()->addMinutes(50), function (): string {
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

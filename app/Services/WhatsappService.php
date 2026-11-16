<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente delgado de la WhatsApp Cloud API (Meta) para el envío de plantillas
 * de mensaje aprobadas. Fuera de la ventana de 24h de una conversación abierta
 * por el paciente, Meta solo permite mensajes de negocio-a-usuario si usan una
 * plantilla pre-aprobada — por eso no existe un método de "texto libre" aquí.
 */
class WhatsappService
{
    public function configurado(): bool
    {
        return filled(config('dentissa.whatsapp.phone_number_id'))
            && filled(config('dentissa.whatsapp.access_token'));
    }

    /**
     * Envía una plantilla a un número de teléfono. Devuelve false tanto si no
     * hay credenciales configuradas como si Meta rechaza el envío — en ambos
     * casos NotificacionService debe caer a correo como respaldo.
     *
     * $botones son los payloads de los botones de respuesta rápida de la
     * plantilla, en el mismo orden en que se aprobaron en Meta. Meta devuelve
     * ese payload al webhook cuando el paciente pulsa el botón.
     */
    public function enviarPlantilla(string $telefono, string $plantilla, array $parametros = [], array $botones = []): bool
    {
        if (! $this->configurado()) {
            Log::info('WhatsApp sin configurar — mensaje no enviado (revisa DENTISSA_WHATSAPP_PHONE_ID / _TOKEN)', [
                'plantilla' => $plantilla,
            ]);

            return false;
        }

        $destinatario = $this->normalizarTelefono($telefono);
        if (! $destinatario) {
            Log::warning('WhatsApp: teléfono inválido; envío omitido', [
                'plantilla' => $plantilla,
            ]);

            return false;
        }

        $phoneNumberId = config('dentissa.whatsapp.phone_number_id');
        $version = config('dentissa.whatsapp.api_version');

        try {
            $response = Http::withToken(config('dentissa.whatsapp.access_token'))
                ->baseUrl("https://graph.facebook.com/{$version}")
                ->timeout(10)
                ->post("/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $destinatario,
                    'type' => 'template',
                    'template' => [
                        'name' => $plantilla,
                        'language' => ['code' => config('dentissa.whatsapp.plantilla_idioma')],
                        'components' => $this->componentes($parametros, $botones),
                    ],
                ]);
        } catch (ConnectionException $e) {
            // Nunca debe tumbar el flujo de agendar/confirmar/cancelar una cita
            // por un problema de red hacia Meta — se degrada a correo.
            Log::warning('WhatsApp: fallo de conexión', ['plantilla' => $plantilla, 'error' => $e->getMessage()]);

            return false;
        }

        if ($response->failed()) {
            Log::warning('WhatsApp rechazó el envío', [
                'plantilla' => $plantilla,
                'status' => $response->status(),
                'codigo_error' => $response->json('error.code'),
            ]);
        }

        return $response->successful();
    }

    private function componentes(array $parametros, array $botones): array
    {
        $componentes = [];

        if ($parametros !== []) {
            $componentes[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn ($valor) => ['type' => 'text', 'text' => (string) $valor],
                    $parametros,
                ),
            ];
        }

        foreach (array_values($botones) as $indice => $payload) {
            $componentes[] = [
                'type' => 'button',
                'sub_type' => 'quick_reply',
                'index' => (string) $indice,
                'parameters' => [['type' => 'payload', 'payload' => (string) $payload]],
            ];
        }

        return $componentes;
    }

    private function normalizarTelefono(string $telefono): ?string
    {
        $telefono = trim($telefono);
        if ($telefono === '' || ! preg_match('/^\+?[0-9\s().-]+$/', $telefono)) {
            return null;
        }

        $digitos = preg_replace('/[^0-9]/', '', $telefono);

        // Los teléfonos mexicanos móviles usan 521 + los diez dígitos del
        // número en esta integración. También aceptamos el formato nacional
        // y el internacional +52 para convertirlos aquí de manera uniforme.
        if (strlen($digitos) === 10) {
            return '521'.$digitos;
        }

        if (preg_match('/^52[0-9]{10}$/', $digitos)) {
            return '521'.substr($digitos, 2);
        }

        if (preg_match('/^521[0-9]{10}$/', $digitos)) {
            return $digitos;
        }

        return null;
    }
}

<?php

namespace App\Services;

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
     */
    public function enviarPlantilla(string $telefono, string $plantilla, array $parametros = []): bool
    {
        if (! $this->configurado()) {
            Log::info('WhatsApp sin configurar — mensaje no enviado (revisa DENTISSA_WHATSAPP_PHONE_ID / _TOKEN)', [
                'telefono' => $telefono,
                'plantilla' => $plantilla,
                'parametros' => $parametros,
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
                    'to' => $this->normalizarTelefono($telefono),
                    'type' => 'template',
                    'template' => [
                        'name' => $plantilla,
                        'language' => ['code' => config('dentissa.whatsapp.plantilla_idioma')],
                        'components' => empty($parametros) ? [] : [[
                            'type' => 'body',
                            'parameters' => array_map(
                                fn ($valor) => ['type' => 'text', 'text' => (string) $valor],
                                $parametros,
                            ),
                        ]],
                    ],
                ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Nunca debe tumbar el flujo de agendar/confirmar/cancelar una cita
            // por un problema de red hacia Meta — se degrada a correo.
            Log::warning('WhatsApp: fallo de conexión', ['telefono' => $telefono, 'plantilla' => $plantilla, 'error' => $e->getMessage()]);

            return false;
        }

        if ($response->failed()) {
            Log::warning('WhatsApp rechazó el envío', [
                'telefono' => $telefono,
                'plantilla' => $plantilla,
                'status' => $response->status(),
                'respuesta' => $response->json(),
            ]);
        }

        return $response->successful();
    }

    private function normalizarTelefono(string $telefono): string
    {
        return preg_replace('/[^0-9]/', '', $telefono);
    }
}

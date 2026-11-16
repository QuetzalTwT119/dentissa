<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Services\RespuestaRecordatorioService;
use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Webhook de WhatsApp Cloud API (etapa 2 del recordatorio).
 *
 * Cuando el paciente pulsa Asistiré / Cancelar / Reagendar en el recordatorio,
 * Meta envía aquí el payload `recordatorio:{id_cita}:{accion}`. Se aplica la
 * misma lógica que los enlaces del correo (RespuestaRecordatorioService) y se
 * le contesta por WhatsApp.
 *
 * Seguridad, en orden:
 * 1. Firma X-Hub-Signature-256 (HMAC-SHA256 del cuerpo con el secreto de la
 *    app): solo Meta puede llamar a este webhook.
 * 2. El teléfono que pulsa el botón debe ser el del paciente de esa cita: un
 *    payload reenviado desde otro número no cambia la cita.
 * 3. Cada mensaje se procesa una sola vez, aunque Meta lo reintente.
 */
class WhatsappWebhookController extends Controller
{
    /** Meta comprueba la URL una vez, al configurar el webhook. */
    public function verificar(Request $request): Response
    {
        $token = (string) config('dentissa.whatsapp.verify_token');

        if (
            $token !== ''
            && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token'))
        ) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('', 403);
    }

    public function recibir(Request $request, RespuestaRecordatorioService $respuestas, WhatsappService $whatsapp): JsonResponse
    {
        if (! $this->firmaValida($request)) {
            return response()->json(['ok' => false], 403);
        }

        foreach ($request->input('entry', []) as $entrada) {
            foreach ($entrada['changes'] ?? [] as $cambio) {
                foreach ($cambio['value']['messages'] ?? [] as $mensaje) {
                    $this->procesar($mensaje, $respuestas, $whatsapp);
                }
            }
        }

        // Siempre 200 si la firma es válida: si no, Meta reintenta durante horas.
        return response()->json(['ok' => true]);
    }

    private function procesar(array $mensaje, RespuestaRecordatorioService $respuestas, WhatsappService $whatsapp): void
    {
        // Botón de plantilla (quick reply) o botón interactivo.
        $payload = $mensaje['button']['payload'] ?? $mensaje['interactive']['button_reply']['id'] ?? null;
        $id = $mensaje['id'] ?? null;
        $telefono = $mensaje['from'] ?? null;

        if (! $id || ! $telefono) {
            return;
        }

        if (! Cache::add('whatsapp:mensaje:'.$id, true, now()->addDays(2))) {
            return; // Reintento de Meta: ya se procesó.
        }

        if (! $payload || ! preg_match('/^recordatorio:(\d+):([a-z]+)$/', $payload, $partes)) {
            // Texto, audio, foto… Nadie lee este número: se avisa al paciente
            // a dónde escribir. No se guarda ni se registra el contenido.
            $this->responderAutomaticamente($telefono, $whatsapp);

            return;
        }

        [, $idCita, $accion] = $partes;
        $cita = Cita::with('paciente')->find($idCita);

        if (! $cita || ! in_array($accion, RespuestaRecordatorioService::ACCIONES, true)
            || ! WhatsappService::mismoTelefono($telefono, $cita->paciente?->telefono)) {
            Log::warning('WhatsApp: respuesta de recordatorio rechazada', ['id_cita' => $idCita, 'accion' => $accion]);

            return;
        }

        $resultado = $respuestas->responder($cita, $accion);
        Log::info('WhatsApp: respuesta de recordatorio aplicada', ['id_cita' => $cita->id, 'resultado' => $resultado]);

        $whatsapp->enviarTexto($telefono, $this->textoRespuesta($resultado, $cita->refresh()));
    }

    /**
     * Una sola respuesta automática por número cada 12 horas: si el paciente
     * manda varios mensajes seguidos, no recibe el mismo aviso varias veces.
     */
    private function responderAutomaticamente(string $telefono, WhatsappService $whatsapp): void
    {
        $clave = 'whatsapp:autorespuesta:'.substr(preg_replace('/\D/', '', $telefono), -10);
        if (! Cache::add($clave, true, now()->addHours(12))) {
            return;
        }

        $whatsapp->enviarTexto($telefono, self::textoAutomatico());
    }

    public static function textoAutomatico(): string
    {
        $telefono = config('dentissa.contacto.telefono');

        return 'Hola, este número solo envía los recordatorios de tus citas y no recibe mensajes. '
            ."Para dudas o cambios, llama o escribe al consultorio al {$telefono}. "
            .'Para agendar una cita: '.route('citas.publica.create');
    }

    private function textoRespuesta(string $resultado, Cita $cita): string
    {
        $fecha = $cita->fecha_hora->copy()->locale('es')->translatedFormat('l j \d\e F, H:i');

        return match ($resultado) {
            RespuestaRecordatorioService::CONFIRMAR => "¡Gracias! Registramos que asistirás a tu cita del {$fecha}. Te esperamos.",
            RespuestaRecordatorioService::CANCELAR => "Tu cita del {$fecha} fue cancelada. Gracias por avisarnos. Cuando quieras volver, agenda aquí: ".route('citas.publica.create'),
            RespuestaRecordatorioService::REAGENDAR => 'Liberamos tu horario. Elige el nuevo día y hora aquí: '.route('citas.publica.create'),
            default => 'Esta cita ya no admite cambios. Si necesitas ayuda, llama al consultorio al '.config('dentissa.contacto.telefono').'.',
        };
    }

    private function firmaValida(Request $request): bool
    {
        $secreto = (string) config('dentissa.whatsapp.app_secret');
        $firma = (string) $request->header('X-Hub-Signature-256');

        if ($secreto === '' || ! str_starts_with($firma, 'sha256=')) {
            return false;
        }

        // HMAC del cuerpo exacto que envió Meta, no del JSON ya interpretado.
        return hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secreto), $firma);
    }
}

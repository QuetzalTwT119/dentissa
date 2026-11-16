<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Services\RespuestaRecordatorioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Etapa 2 del recordatorio: el paciente responde con los botones del mensaje
 * de WhatsApp y Meta avisa al webhook. Los mensajes se firman igual que Meta.
 */
class WhatsappWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETO = 'secreto-de-la-app-de-prueba';

    private Cita $cita;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2030-01-09 10:00');
        config([
            'dentissa.whatsapp.app_secret' => self::SECRETO,
            'dentissa.whatsapp.verify_token' => 'token-que-elegimos',
            'dentissa.whatsapp.phone_number_id' => 'phone-id',
            'dentissa.whatsapp.access_token' => 'token-meta',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.respuesta']]])]);

        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5512345678', 'email' => 'ana@example.test']);
        $this->cita = Cita::create([
            'id_paciente' => $paciente->id, 'id_servicio' => $servicio->id, 'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => Carbon::parse('2030-01-10 09:00'), 'duracion_estimada' => 60, 'estado' => EstadoCita::Agendada,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_meta_verifica_la_url_solo_con_el_token_correcto(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=token-que-elegimos&hub.challenge=12345')
            ->assertOk()
            ->assertSeeText('12345');

        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=otro&hub.challenge=12345')->assertForbidden();
    }

    public function test_asistire_confirma_la_cita_y_contesta_al_paciente(): void
    {
        $this->enviar($this->boton('confirmar'))->assertOk();

        $this->cita->refresh();
        $this->assertSame(EstadoCita::Confirmada, $this->cita->estado);
        $this->assertNotNull($this->cita->asistencia_confirmada_en);
        Http::assertSent(fn (Request $r) => $r['type'] === 'text'
            && $r['to'] === '5215512345678'
            && str_contains($r['text']['body'], 'Registramos que asistirás'));
    }

    public function test_reagendar_libera_el_horario_y_manda_el_enlace_de_la_agenda(): void
    {
        $this->enviar($this->boton('reagendar'))->assertOk();

        $this->assertSame(EstadoCita::Cancelada, $this->cita->fresh()->estado);
        $this->assertSame(RespuestaRecordatorioService::MOTIVO_REAGENDAR, $this->cita->fresh()->motivo_cancelacion);
        Http::assertSent(fn (Request $r) => str_contains($r['text']['body'] ?? '', route('citas.publica.create')));
    }

    public function test_un_mensaje_sin_firma_o_con_firma_falsa_se_rechaza(): void
    {
        $cuerpo = json_encode($this->boton('cancelar'));

        $this->call('POST', '/webhooks/whatsapp', [], [], [], ['CONTENT_TYPE' => 'application/json'], $cuerpo)->assertForbidden();
        $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $cuerpo, 'otro-secreto'),
        ], $cuerpo)->assertForbidden();

        $this->assertSame(EstadoCita::Agendada, $this->cita->fresh()->estado);
        Http::assertNothingSent();
    }

    public function test_un_boton_reenviado_desde_otro_numero_no_cambia_la_cita(): void
    {
        $this->enviar($this->boton('cancelar', desde: '5215599999999'))->assertOk();

        $this->assertSame(EstadoCita::Agendada, $this->cita->fresh()->estado);
        Http::assertNothingSent();
    }

    public function test_si_meta_reintenta_el_mismo_mensaje_solo_se_procesa_una_vez(): void
    {
        $mensaje = $this->boton('confirmar');

        $this->enviar($mensaje)->assertOk();
        $this->enviar($mensaje)->assertOk();

        Http::assertSentCount(1);
    }

    public function test_una_cita_pasada_contesta_que_ya_no_admite_cambios(): void
    {
        $this->cita->update(['fecha_hora' => Carbon::parse('2030-01-08 09:00')]);

        $this->enviar($this->boton('cancelar'))->assertOk();

        $this->assertSame(EstadoCita::Agendada, $this->cita->fresh()->estado);
        Http::assertSent(fn (Request $r) => str_contains($r['text']['body'] ?? '', 'ya no admite cambios'));
    }

    public function test_un_mensaje_de_texto_recibe_la_respuesta_automatica_sin_cambiar_citas(): void
    {
        $this->enviar($this->texto('wamid.texto-1', '5215512345678', '¿Puedo llegar 10 minutos tarde?'))->assertOk();

        $this->assertSame(EstadoCita::Agendada, $this->cita->fresh()->estado);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r['type'] === 'text'
            && $r['to'] === '5215512345678'
            && str_contains($r['text']['body'], 'solo envía los recordatorios')
            && str_contains($r['text']['body'], config('dentissa.contacto.telefono'))
            && str_contains($r['text']['body'], route('citas.publica.create')));
    }

    public function test_la_respuesta_automatica_se_manda_una_vez_cada_12_horas_por_numero(): void
    {
        $this->enviar($this->texto('wamid.a', '5215512345678', 'hola'))->assertOk();
        $this->enviar($this->texto('wamid.b', '5215512345678', '¿siguen ahí?'))->assertOk();
        $this->enviar($this->texto('wamid.c', '5215587654321', 'hola'))->assertOk();
        Http::assertSentCount(2);

        Carbon::setTestNow(now()->addHours(13));
        $this->enviar($this->texto('wamid.d', '5215512345678', 'hola otra vez'))->assertOk();
        Http::assertSentCount(3);
    }

    public function test_si_la_respuesta_automatica_falla_el_siguiente_mensaje_lo_vuelve_a_intentar(): void
    {
        // Meta rechaza el primer envío (por ejemplo, un token vencido) y acepta el segundo.
        // Cliente nuevo: el de setUp() siempre responde éxito y tendría prioridad.
        Http::swap(new HttpFactory);
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['error' => ['code' => 190]], 401)
            ->push(['messages' => [['id' => 'wamid.ok']]])]);

        $this->enviar($this->texto('wamid.f1', '5215512345678', 'hola'))->assertOk();
        $this->enviar($this->texto('wamid.f2', '5215512345678', 'hola de nuevo'))->assertOk();

        // Sin el arreglo, el número quedaba bloqueado 12 h y el segundo no se intentaba.
        Http::assertSentCount(2);
    }

    public function test_los_avisos_de_estado_de_meta_no_reciben_respuesta(): void
    {
        $estado = $this->boton('confirmar');
        unset($estado['entry'][0]['changes'][0]['value']['messages']);
        $estado['entry'][0]['changes'][0]['value']['statuses'] = [['id' => 'wamid.enviado', 'status' => 'delivered', 'recipient_id' => '5215512345678']];

        $this->enviar($estado)->assertOk();

        Http::assertNothingSent();
    }

    /** @return array<string, mixed> */
    private function texto(string $id, string $desde, string $cuerpo): array
    {
        $datos = $this->boton('confirmar');
        $datos['entry'][0]['changes'][0]['value']['messages'][0] = ['id' => $id, 'from' => $desde, 'type' => 'text', 'text' => ['body' => $cuerpo]];

        return $datos;
    }

    /** @return array<string, mixed> */
    private function boton(string $accion, string $desde = '5215512345678'): array
    {
        return ['object' => 'whatsapp_business_account', 'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'messaging_product' => 'whatsapp',
            'messages' => [[
                'id' => 'wamid.'.$accion.$desde,
                'from' => $desde,
                'type' => 'button',
                'button' => ['payload' => "recordatorio:{$this->cita->id}:{$accion}", 'text' => 'Botón'],
            ]],
        ]]]]]];
    }

    private function enviar(array $datos): TestResponse
    {
        $cuerpo = json_encode($datos);

        return $this->call('POST', '/webhooks/whatsapp', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $cuerpo, self::SECRETO),
        ], $cuerpo);
    }
}

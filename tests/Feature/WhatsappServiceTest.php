<?php

namespace Tests\Feature;

use App\Mail\CitaNotificacion;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Services\NotificacionService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WhatsappServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dentissa.whatsapp.phone_number_id' => 'phone-number-id-de-prueba',
            'dentissa.whatsapp.access_token' => 'token-de-prueba',
            'dentissa.whatsapp.api_version' => 'v21.0',
            'dentissa.whatsapp.plantilla_idioma' => 'es_MX',
        ]);
    }

    public function test_normaliza_numeros_mx_antes_de_enviarlos_a_la_api(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test']],
            ]),
        ]);

        $whatsapp = app(WhatsappService::class);
        foreach ([
            '5512345678' => '5215512345678',
            '+52 55 1234 5678' => '5215512345678',
            '+52 (1) 55-1234-5678' => '5215512345678',
            '5215512345678' => '5215512345678',
        ] as $entrada => $destinatario) {
            $this->assertTrue($whatsapp->enviarPlantilla($entrada, 'cita_agendada', ['Nombre de prueba']));
        }

        Http::assertSentCount(4);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.facebook.com/v21.0/phone-number-id-de-prueba/messages'
            && $request['messaging_product'] === 'whatsapp'
            && $request['to'] === '5215512345678'
            && $request['type'] === 'template'
            && $request['template']['name'] === 'cita_agendada'
            && $request['template']['language']['code'] === 'es_MX');
    }

    public function test_comando_de_prueba_no_envia_sin_confirmacion_y_usa_hello_world_en_ingles(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test']],
            ]),
        ]);

        $sinConfirmar = Artisan::call('whatsapp:probar', [
            'telefono' => '5512345678',
        ]);

        $this->assertSame(1, $sinConfirmar);
        Http::assertNothingSent();

        $confirmado = Artisan::call('whatsapp:probar', [
            'telefono' => '5512345678',
            '--confirmar' => true,
        ]);

        $this->assertSame(0, $confirmado);
        Http::assertSent(fn (Request $request) => $request['template']['name'] === 'hello_world'
            && $request['template']['language']['code'] === 'en_US'
            && $request['to'] === '5215512345678');
    }

    public function test_no_envia_a_meta_si_el_telefono_no_es_mx_valido(): void
    {
        Http::preventStrayRequests();

        $resultado = app(WhatsappService::class)->enviarPlantilla(
            '7551131833333333333',
            'cita_agendada',
            ['Nombre de prueba'],
        );

        $this->assertFalse($resultado);
        Http::assertNothingSent();
    }

    public function test_si_no_hay_credenciales_no_intenta_enviar_ni_registra_datos_personales(): void
    {
        config([
            'dentissa.whatsapp.phone_number_id' => null,
            'dentissa.whatsapp.access_token' => null,
        ]);
        Http::preventStrayRequests();
        Log::shouldReceive('info')
            ->once()
            ->with('WhatsApp sin configurar — mensaje no enviado (revisa DENTISSA_WHATSAPP_PHONE_ID / _TOKEN)', [
                'plantilla' => 'cita_agendada',
            ]);

        $resultado = app(WhatsappService::class)->enviarPlantilla(
            '+52 55 1234 5678',
            'cita_agendada',
            ['Nombre confidencial'],
        );

        $this->assertFalse($resultado);
        Http::assertNothingSent();
    }

    public function test_un_telefono_invalido_usa_correo_de_respaldo_sin_llamar_a_meta(): void
    {
        Http::preventStrayRequests();
        Mail::fake();

        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $paciente = Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'Ficticio',
            'telefono' => '555-ABC-1234',
            'email' => 'paciente@example.test',
        ]);
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => Carbon::tomorrow()->setTime(9, 0),
            'duracion_estimada' => $tratamiento->duracion_estimada,
        ]);

        app(NotificacionService::class)->citaAgendada($cita->load(['paciente', 'tratamiento']));

        Http::assertNothingSent();
        Mail::assertSent(CitaNotificacion::class);
        $this->assertDatabaseHas('notificaciones', [
            'id_paciente' => $paciente->id,
            'id_cita' => $cita->id,
            'tipo' => 'email',
        ]);
    }
}

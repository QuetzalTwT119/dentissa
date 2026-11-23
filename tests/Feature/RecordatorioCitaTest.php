<?php

namespace Tests\Feature;

use App\Enums\CanalNotificacion;
use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Mail\RecordatorioCita;
use App\Models\Cita;
use App\Models\Notificacion;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\RespuestaRecordatorioService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Recordatorio 24 h antes (comando programado) y respuesta del paciente con
 * los enlaces firmados del correo. Hora fija: miércoles 9 ene 2030, 10:00.
 */
class RecordatorioCitaTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;

    private Tratamiento $tratamiento;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2030-01-09 10:00:00');
        Mail::fake();

        $this->servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $this->tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ── Envío del recordatorio ────────────────────────────────────────────

    public function test_manda_el_recordatorio_por_whatsapp_y_correo_una_sola_vez(): void
    {
        $this->configurarWhatsapp();
        Http::fake(['https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]])]);
        $cita = $this->cita('2030-01-10 09:00');

        $this->artisan('dentissa:enviar-recordatorios')->assertSuccessful();
        $this->artisan('dentissa:enviar-recordatorios')->assertSuccessful();

        Mail::assertSent(RecordatorioCita::class, 1);
        Mail::assertSent(RecordatorioCita::class, fn (RecordatorioCita $correo) => $correo->hasTo($cita->paciente->email)
            // La fecha va en español aunque APP_LOCALE sea "en".
            && str_contains($correo->mensaje, '10 ene. 2030, 09:00'));
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($cita) {
            $plantilla = $request['template'];
            $botones = collect($plantilla['components'])->where('type', 'button')->values();

            return $plantilla['name'] === 'cita_recordatorio'
                && $botones->count() === 3
                && $botones[0]['parameters'][0]['payload'] === "recordatorio:{$cita->id}:confirmar";
        });

        $this->assertNotNull($cita->fresh()->recordatorio_enviado_en);
        $registros = Notificacion::where('id_cita', $cita->id)->get();
        $this->assertCount(2, $registros);
        $this->assertEqualsCanonicalizing(['whatsapp', 'email'], $registros->map(fn ($n) => $n->getRawOriginal('tipo'))->all());
        $this->assertTrue($registros->every(fn ($n) => $n->getRawOriginal('canal') === CanalNotificacion::CitaRecordatorio->value));
    }

    public function test_si_whatsapp_falla_el_correo_sale_de_todas_formas(): void
    {
        $this->configurarWhatsapp();
        Http::fake(['https://graph.facebook.com/*' => Http::response(['error' => ['code' => 132001]], 400)]);
        $cita = $this->cita('2030-01-10 09:00');

        $this->artisan('dentissa:enviar-recordatorios')->assertSuccessful();

        Mail::assertSent(RecordatorioCita::class, 1);
        $this->assertSame(['email'], Notificacion::where('id_cita', $cita->id)->get()->map(fn ($n) => $n->getRawOriginal('tipo'))->all());
    }

    public function test_si_el_correo_falla_el_comando_sigue_con_las_demas_citas(): void
    {
        $primera = $this->cita('2030-01-10 09:00');
        $segunda = $this->cita('2030-01-10 09:30');
        Mail::shouldReceive('to')->andThrow(new Exception('SMTP caído'));

        $this->artisan('dentissa:enviar-recordatorios')->assertSuccessful();

        foreach ([$primera, $segunda] as $cita) {
            $this->assertNotNull($cita->fresh()->recordatorio_enviado_en);
            // Ningún canal funcionó: queda el intento registrado sin tipo.
            $this->assertNull(Notificacion::where('id_cita', $cita->id)->sole()->getRawOriginal('tipo'));
        }
    }

    public function test_ignora_citas_canceladas_atendidas_ya_pasadas_o_de_mas_de_24_horas(): void
    {
        $citas = [
            $this->cita('2030-01-10 09:00', EstadoCita::Cancelada),
            $this->cita('2030-01-10 09:00', EstadoCita::NoAsistio),
            $this->cita('2030-01-10 09:00', EstadoCita::Completada),
            $this->cita('2030-01-09 09:00'),
            $this->cita('2030-01-10 10:15'),
        ];

        $this->artisan('dentissa:enviar-recordatorios')->assertSuccessful();

        Mail::assertNothingSent();
        foreach ($citas as $cita) {
            $this->assertNull($cita->fresh()->recordatorio_enviado_en);
        }
    }

    public function test_tambien_recuerda_citas_agendadas_pendientes_de_confirmar(): void
    {
        $this->cita('2030-01-10 09:00', EstadoCita::Agendada);

        $this->artisan('dentissa:enviar-recordatorios')->assertSuccessful();

        Mail::assertSent(RecordatorioCita::class, 1);
    }

    public function test_no_recuerda_una_cita_agendada_con_menos_de_24_horas_de_anticipacion(): void
    {
        $cita = $this->cita('2030-01-10 09:00', creadaEl: '2030-01-09 09:30');

        $this->artisan('dentissa:enviar-recordatorios')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNull($cita->fresh()->recordatorio_enviado_en);
    }

    public function test_reagendar_borra_la_marca_y_la_nueva_fecha_recibe_su_recordatorio(): void
    {
        $cita = $this->cita('2030-01-10 09:00');
        $cita->update(['recordatorio_enviado_en' => now(), 'asistencia_confirmada_en' => now()]);

        $this->actingAs(User::factory()->create(['rol' => RolUsuario::SuperAdmin]))
            ->put(route('citas.update', $cita), ['fecha' => '2030-01-10', 'hora' => '10:00'])
            ->assertSessionHasNoErrors();

        $cita->refresh();
        $this->assertNull($cita->recordatorio_enviado_en);
        $this->assertNull($cita->asistencia_confirmada_en);

        $this->artisan('dentissa:enviar-recordatorios')->assertSuccessful();
        Mail::assertSent(RecordatorioCita::class, 1);
    }

    public function test_el_scheduler_corre_el_comando_cada_15_minutos(): void
    {
        Artisan::call('schedule:list');

        $salida = Artisan::output();
        $this->assertStringContainsString('dentissa:enviar-recordatorios', $salida);
        $this->assertStringContainsString('*/15 * * * *', $salida);
    }

    // ── Respuesta del paciente ────────────────────────────────────────────

    public function test_el_correo_trae_un_enlace_firmado_por_cada_respuesta(): void
    {
        $cita = $this->cita('2030-01-10 09:00');

        $html = (new RecordatorioCita($cita, 'Recordatorio de tu cita', 'Hola'))->render();

        foreach (RespuestaRecordatorioService::ACCIONES as $accion) {
            $this->assertStringContainsString(e(RecordatorioCita::enlaces($cita)[$accion]), $html);
        }
        $this->assertStringContainsString('signature=', $html);
    }

    public function test_abrir_el_enlace_solo_pregunta_y_no_cambia_la_cita(): void
    {
        $cita = $this->cita('2030-01-10 09:00', EstadoCita::Agendada);

        $this->get($this->enlace($cita, 'cancelar'))
            ->assertOk()
            ->assertSee('¿Cancelar tu cita?')
            ->assertSee('Jueves 10 de enero de 2030 · 09:00 hrs')
            ->assertSee('Sí, cancelar mi cita');

        // Gmail o un antivirus pueden abrir el enlace solos: no debe cancelar.
        $this->assertSame(EstadoCita::Agendada, $cita->fresh()->estado);
    }

    public function test_confirmar_marca_la_asistencia_y_avisa_al_personal_una_vez(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $cita = $this->cita('2030-01-10 09:00', EstadoCita::Agendada);

        $this->followingRedirects()->post($this->enlace($cita, 'confirmar', 'citas.recordatorio.responder'))
            ->assertOk()
            ->assertSee('¡Gracias! Te esperamos.');
        $this->post($this->enlace($cita, 'confirmar', 'citas.recordatorio.responder'));

        $cita->refresh();
        $this->assertSame(EstadoCita::Confirmada, $cita->estado);
        $this->assertNotNull($cita->asistencia_confirmada_en);
        $this->assertNotNull($cita->fecha_confirmacion);
        $this->assertSame(1, NotificacionSistema::where('user_id', $admin->id)->where('evento', 'asistencia_confirmada')->count());
    }

    public function test_la_agenda_muestra_que_el_paciente_confirmo_su_asistencia(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $confirmada = $this->cita('2030-01-10 09:00');
        $this->cita('2030-01-10 09:30');
        $this->post($this->enlace($confirmada, 'confirmar', 'citas.recordatorio.responder'));

        $this->actingAs($admin)->get(route('citas.index', ['fecha' => '2030-01-10']))
            ->assertOk()
            ->assertSee('Paciente confirmó asistencia');
        $this->actingAs($admin)->get(route('citas.index', ['vista' => 'semana', 'semana' => '2030-01-07']))
            ->assertOk()
            ->assertSee('Confirmó asistencia');

        // Solo la cita que respondió lleva la marca.
        $html = $this->actingAs($admin)->get(route('citas.index', ['vista' => 'semana', 'semana' => '2030-01-07']))->getContent();
        $this->assertSame(1, substr_count($html, 'data-asistencia-confirmada'));
    }

    public function test_cancelar_libera_el_horario_y_avisa_al_personal(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $cita = $this->cita('2030-01-10 09:00');

        $this->followingRedirects()->post($this->enlace($cita, 'cancelar', 'citas.recordatorio.responder'))
            ->assertOk()
            ->assertSee('Tu cita fue cancelada.');

        $cita->refresh();
        $this->assertSame(EstadoCita::Cancelada, $cita->estado);
        $this->assertNull($cita->cancelada_por);
        $this->assertSame(RespuestaRecordatorioService::MOTIVO_CANCELAR, $cita->motivo_cancelacion);
        $this->assertTrue(NotificacionSistema::where('user_id', $admin->id)->where('evento', 'cancelada_paciente')->exists());
    }

    public function test_reagendar_libera_el_horario_y_lleva_a_la_agenda_en_linea(): void
    {
        User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $cita = $this->cita('2030-01-10 09:00');

        $this->followingRedirects()->post($this->enlace($cita, 'reagendar', 'citas.recordatorio.responder'))
            ->assertOk()
            ->assertSee('Liberamos tu horario.')
            ->assertSee(route('citas.publica.create').'?expires=', false)
            ->assertSee('reagendar='.$cita->id, false);

        $cita->refresh();
        $this->assertSame(EstadoCita::Cancelada, $cita->estado);
        $this->assertSame(RespuestaRecordatorioService::MOTIVO_REAGENDAR, $cita->motivo_cancelacion);
        $this->assertTrue(NotificacionSistema::where('evento', 'reagendar_paciente')->exists());
    }

    /** 23/11/2026: el link de reagendar abre en el calendario con el tratamiento y los datos. */
    public function test_el_enlace_de_reagendar_abre_el_calendario_con_los_datos_llenos(): void
    {
        $this->servicio->tratamientos()->attach($this->tratamiento);
        $cita = $this->cita('2030-01-10 09:00');
        $paciente = $cita->paciente;
        $enlace = RespuestaRecordatorioService::enlaceReagendar($cita);

        $this->get($enlace)->assertOk()
            ->assertSee('data-reagendar-aviso', false)
            ->assertSee('Cambio de cita: Limpieza Dental.')
            ->assertSee('paso: 1,', false)
            ->assertSee($paciente->email)
            ->assertSee($paciente->telefono);

        // Sin firma, con otro número de cita o caducado: la agenda normal y sin datos.
        $alterado = str_replace('reagendar='.$cita->id, 'reagendar='.($cita->id + 1), $enlace);
        foreach ([route('citas.publica.create', ['reagendar' => $cita->id]), $alterado] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('data-reagendar-aviso', false)
                ->assertSee('paso: 0,', false)
                ->assertDontSee($paciente->email);
        }
        Carbon::setTestNow(now()->addDays(RespuestaRecordatorioService::DIAS_ENLACE_REAGENDAR)->addMinute());
        $this->get($enlace)->assertOk()->assertDontSee('data-reagendar-aviso', false)->assertDontSee($paciente->email);
    }

    public function test_una_cita_pasada_o_cancelada_ya_no_admite_respuesta(): void
    {
        $pasada = $this->cita('2030-01-09 09:00');
        $cancelada = $this->cita('2030-01-10 09:00', EstadoCita::Cancelada);

        foreach ([$pasada, $cancelada] as $cita) {
            $this->get($this->enlace($cita, 'confirmar'))->assertOk()->assertSee('Esta cita ya no admite cambios.');
            $this->followingRedirects()->post($this->enlace($cita, 'confirmar', 'citas.recordatorio.responder'))
                ->assertOk()
                ->assertSee('Esta cita ya no admite cambios.');
        }

        $this->assertSame(EstadoCita::Confirmada, $pasada->fresh()->estado);
        $this->assertNull($pasada->fresh()->asistencia_confirmada_en);
        $this->assertSame(EstadoCita::Cancelada, $cancelada->fresh()->estado);
    }

    public function test_un_enlace_sin_firma_o_alterado_se_rechaza(): void
    {
        $cita = $this->cita('2030-01-10 09:00');
        $otra = $this->cita('2030-01-10 11:00');

        $this->get("/cita/{$cita->id}/recordatorio/cancelar")->assertForbidden();

        // Cambiar el número de cita invalida la firma.
        $alterado = str_replace("/cita/{$cita->id}/", "/cita/{$otra->id}/", $this->enlace($cita, 'cancelar', 'citas.recordatorio.responder'));
        $this->post($alterado)->assertForbidden();

        $this->assertSame(EstadoCita::Confirmada, $otra->fresh()->estado);
    }

    // ── Ayudantes ─────────────────────────────────────────────────────────

    private function configurarWhatsapp(): void
    {
        config([
            'dentissa.whatsapp.phone_number_id' => 'phone-number-id-de-prueba',
            'dentissa.whatsapp.access_token' => 'token-de-prueba',
            'dentissa.whatsapp.plantilla_idioma' => 'es_MX',
        ]);
    }

    private function enlace(Cita $cita, string $accion, string $ruta = 'citas.recordatorio.show'): string
    {
        return RecordatorioCita::urlFirmada($ruta, $cita, $accion);
    }

    private function cita(string $fechaHora, EstadoCita $estado = EstadoCita::Confirmada, string $creadaEl = '2030-01-02 12:00'): Cita
    {
        $paciente = Paciente::create([
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '55'.random_int(10000000, 99999999),
            'email' => 'ana'.random_int(1, 999999).'@example.test',
        ]);

        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $this->servicio->id,
            'id_tratamiento' => $this->tratamiento->id,
            'fecha_hora' => Carbon::parse($fechaHora),
            'duracion_estimada' => 60,
            'estado' => $estado,
        ]);

        // created_at decide si la cita se agendó con menos de 24 h de anticipación.
        $cita->forceFill(['created_at' => Carbon::parse($creadaEl)])->saveQuietly();

        return $cita->load(['paciente', 'tratamiento']);
    }
}

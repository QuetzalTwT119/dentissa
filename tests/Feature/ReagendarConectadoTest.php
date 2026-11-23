<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Mail\RecordatorioCita;
use App\Models\Cita;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\RespuestaRecordatorioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 23/11/2026: el paciente reagenda desde el recordatorio. La cita nueva queda
 * conectada con la vieja, recibe "tu cita se movió" y no vuelve a escribir sus
 * datos ni a aceptar el aviso de privacidad.
 */
class ReagendarConectadoTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;

    private Tratamiento $tratamiento;

    private Paciente $paciente;

    private Cita $vieja;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2030-01-09 10:00:00');
        Mail::fake();
        config([
            'dentissa.whatsapp.phone_number_id' => 'phone-number-id-de-prueba',
            'dentissa.whatsapp.access_token' => 'token-de-prueba',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.prueba']]])]);

        User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $this->servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $this->tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);
        $this->servicio->tratamientos()->attach($this->tratamiento);

        $this->paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5512345678', 'email' => 'ana@example.test']);
        $this->paciente->forceFill(['aviso_privacidad_aceptado_en' => '2030-01-02 12:00:00'])->save();
        $this->vieja = Cita::create([
            'id_paciente' => $this->paciente->id, 'id_servicio' => $this->servicio->id, 'id_tratamiento' => $this->tratamiento->id,
            'fecha_hora' => Carbon::parse('2030-01-10 09:00'), 'duracion_estimada' => 60, 'estado' => EstadoCita::Confirmada,
        ]);

        // El paciente pulsa "Reagendar" en el recordatorio: se libera el horario.
        $this->post(RecordatorioCita::urlFirmada('citas.recordatorio.responder', $this->vieja, 'reagendar'));
        $this->assertSame(EstadoCita::Cancelada, $this->vieja->fresh()->estado);
    }

    private function datos(array $extra = []): array
    {
        return $extra + [
            'id_servicio' => $this->servicio->id,
            'id_tratamiento' => $this->tratamiento->id,
            'fecha' => '2030-01-11',
            'hora' => '09:00',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5512345678',
            'email' => 'ana@example.test',
            'reagendar' => RespuestaRecordatorioService::tokenReagendar($this->vieja->fresh()),
        ];
    }

    public function test_el_ultimo_paso_solo_confirma_sin_volver_a_pedir_datos(): void
    {
        $this->get(RespuestaRecordatorioService::enlaceReagendar($this->vieja))->assertOk()
            ->assertSee('Confirma tu cambio de cita')
            ->assertSee('data-datos-reagendar', false)
            ->assertSee('Confirmar cambio de cita')
            ->assertSee('name="reagendar"', false)
            ->assertDontSee('name="acepta_aviso"', false);
    }

    public function test_reagendar_conecta_las_citas_y_manda_tu_cita_se_movio(): void
    {
        // Sin casilla del aviso: ya lo había aceptado.
        $this->post(route('citas.publica.store'), $this->datos())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Tu cita quedó reagendada. Te esperamos en el nuevo horario.');

        $nueva = Cita::whereKeyNot($this->vieja->id)->sole();
        $this->assertSame($nueva->id, $this->vieja->fresh()->id_cita_nueva);
        $this->assertSame($this->paciente->id, $nueva->id_paciente);
        // La fecha del consentimiento original se conserva.
        $this->assertSame('2030-01-02 12:00:00', $this->paciente->fresh()->aviso_privacidad_aceptado_en->format('Y-m-d H:i:s'));

        // WhatsApp: plantilla de reagendada, no la de confirmada.
        Http::assertSent(fn (Request $r) => ($r['template']['name'] ?? null) === 'cita_reagendada');
        Http::assertNotSent(fn (Request $r) => ($r['template']['name'] ?? null) === 'cita_confirmada');

        // La campana: "nuevo horario" y el "horario liberado" queda atendido.
        $this->assertTrue(NotificacionSistema::where('evento', 'reagendada_paciente')->where('id_cita', $nueva->id)->exists());
        $this->assertFalse(NotificacionSistema::where('evento', 'reagendar_paciente')->whereNull('atendida_en')->exists());

        // La agenda dice a dónde se movió.
        $doctora = User::where('rol', RolUsuario::SuperAdmin)->first();
        $this->actingAs($doctora)->get(route('citas.index', ['fecha' => '2030-01-10']))->assertOk()
            ->assertSee('data-reagendada-a', false)
            ->assertSee('Reagendada por el paciente');

        // El botón viejo ya dice a dónde se reagendó.
        $this->followingRedirects()->post(RecordatorioCita::urlFirmada('citas.recordatorio.responder', $this->vieja, 'confirmar'))
            ->assertOk()->assertSee('Esta cita ya se reagendó al');
    }

    public function test_el_mismo_token_no_conecta_dos_citas(): void
    {
        $datos = $this->datos();
        $this->post(route('citas.publica.store'), $datos)->assertSessionHasNoErrors();
        $primera = $this->vieja->fresh()->id_cita_nueva;

        // Segunda vez con el mismo token: cita normal, pide el aviso.
        $this->post(route('citas.publica.store'), ['hora' => '11:00'] + $datos)->assertSessionHasErrors('acepta_aviso');
        $this->post(route('citas.publica.store'), ['hora' => '11:00', 'acepta_aviso' => '1'] + $datos)->assertSessionHasNoErrors();
        $this->assertSame($primera, $this->vieja->fresh()->id_cita_nueva);
    }

    public function test_solo_cambian_dia_y_hora_aunque_el_formulario_mande_otra_cosa(): void
    {
        $otro = Tratamiento::create(['nombre' => 'Blanqueamiento', 'duracion_estimada' => 60, 'activo' => true]);
        $this->servicio->tratamientos()->attach($otro);

        $this->get(RespuestaRecordatorioService::enlaceReagendar($this->vieja))->assertOk()
            ->assertSee('paso: 1,', false);

        $this->post(route('citas.publica.store'), $this->datos([
            'id_tratamiento' => $otro->id, 'nombre' => 'Intruso', 'email' => 'otro@example.test',
        ]))->assertSessionHasNoErrors();

        $nueva = Cita::whereKeyNot($this->vieja->id)->sole();
        $this->assertSame($this->tratamiento->id, $nueva->id_tratamiento);
        $this->assertSame('Ana', $nueva->paciente->nombre);
        $this->assertSame('ana@example.test', $nueva->paciente->email);
    }

    public function test_si_nunca_acepto_el_aviso_solo_se_pide_la_casilla(): void
    {
        $this->paciente->forceFill(['aviso_privacidad_aceptado_en' => null])->save();

        $this->get(RespuestaRecordatorioService::enlaceReagendar($this->vieja))->assertOk()
            ->assertSee('data-datos-reagendar', false)
            ->assertSee('name="acepta_aviso"', false)
            ->assertDontSee('autocomplete="given-name"', false);

        $this->post(route('citas.publica.store'), $this->datos())->assertSessionHasErrors('acepta_aviso');
        $this->post(route('citas.publica.store'), $this->datos(['acepta_aviso' => '1']))->assertSessionHasNoErrors();
        $this->assertNotNull($this->vieja->fresh()->id_cita_nueva);
        $this->assertNotNull($this->paciente->fresh()->aviso_privacidad_aceptado_en);
    }

    /** 23/11/2026: se conserva la oferta que ya tenía (si sigue vigente), pero no se gana una nueva. */
    public function test_al_reagendar_solo_se_conserva_la_oferta_que_ya_tenia(): void
    {
        $oferta = \App\Models\Oferta::create([
            'titulo' => 'Limpieza de fin de año', 'descripcion' => 'Prueba', 'id_tratamiento' => $this->tratamiento->id,
            'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 20,
            'fecha_inicio' => '2030-01-01', 'fecha_fin' => '2030-02-01', 'cupo_total' => 0, 'estado' => 'activa',
        ]);

        // Sin oferta en la cita vieja: la nueva tampoco, aunque haya una vigente.
        $this->post(route('citas.publica.store'), $this->datos(['id_oferta' => $oferta->id]))->assertSessionHasNoErrors();
        $this->assertNull(Cita::whereKeyNot($this->vieja->id)->sole()->id_oferta);

        // Con oferta en la cita vieja: se conserva.
        $conOferta = Cita::create([
            'id_paciente' => $this->paciente->id, 'id_servicio' => $this->servicio->id, 'id_tratamiento' => $this->tratamiento->id,
            'id_oferta' => $oferta->id, 'fecha_hora' => Carbon::parse('2030-01-14 09:00'), 'duracion_estimada' => 60, 'estado' => EstadoCita::Confirmada,
        ]);
        $this->post(RecordatorioCita::urlFirmada('citas.recordatorio.responder', $conOferta, 'reagendar'));
        $this->get(RespuestaRecordatorioService::enlaceReagendar($conOferta))->assertSee("idOferta: {$oferta->id}", false);
        $this->post(route('citas.publica.store'), $this->datos([
            'fecha' => '2030-01-15', 'reagendar' => RespuestaRecordatorioService::tokenReagendar($conOferta->fresh()),
        ]))->assertSessionHasNoErrors();
        $this->assertSame($oferta->id, $conOferta->fresh()->citaNueva->id_oferta);
    }

    public function test_otro_celular_o_token_falso_no_reagenda(): void
    {
        $this->post(route('citas.publica.store'), $this->datos(['telefono' => '5598765432']))->assertSessionHasErrors('acepta_aviso');
        $this->post(route('citas.publica.store'), $this->datos(['reagendar' => 'token-inventado']))->assertSessionHasErrors('acepta_aviso');
        $this->assertNull($this->vieja->fresh()->id_cita_nueva);

        // Con el aviso aceptado se agenda como cita normal ("confirmamos").
        $this->post(route('citas.publica.store'), $this->datos(['telefono' => '5598765432', 'acepta_aviso' => '1']))->assertSessionHasNoErrors();
        $this->assertNull($this->vieja->fresh()->id_cita_nueva);
        Http::assertSent(fn (Request $r) => ($r['template']['name'] ?? null) === 'cita_confirmada');
    }
}

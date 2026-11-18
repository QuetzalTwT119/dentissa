<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Mail\RecordatorioCita;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * dentissa:probar-recordatorio manda el recordatorio de una cita en el momento,
 * sin la ventana de 24 h. Solo envía con --confirmar.
 */
class ProbarRecordatorioTest extends TestCase
{
    use RefreshDatabase;

    private Cita $cita;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2030-01-09 10:00');
        Mail::fake();
        config([
            'dentissa.whatsapp.phone_number_id' => 'phone-id',
            'dentissa.whatsapp.access_token' => 'token-meta',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.prueba']]])]);

        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5512345678', 'email' => 'ana.prueba@example.test']);
        // Agendada hace una hora para dentro de 3 días: el envío programado no la tocaría.
        $this->cita = Cita::create([
            'id_paciente' => $paciente->id, 'id_servicio' => $servicio->id, 'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => Carbon::parse('2030-01-12 10:00'), 'duracion_estimada' => 60, 'estado' => EstadoCita::Confirmada,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_sin_confirmar_solo_muestra_la_cita_con_datos_enmascarados(): void
    {
        $this->artisan('dentissa:probar-recordatorio', ['cita' => $this->cita->id])
            ->expectsOutputToContain('Limpieza Dental')
            ->expectsOutputToContain('••••••5678')
            ->expectsOutputToContain('a•••@example.test')
            ->expectsOutputToContain('No se envió nada')
            ->doesntExpectOutputToContain('5512345678')
            ->assertSuccessful();

        Http::assertNothingSent();
        Mail::assertNothingSent();
        $this->assertNull($this->cita->fresh()->recordatorio_enviado_en);
    }

    public function test_con_confirmar_envia_la_plantilla_y_el_correo_aunque_falten_dias(): void
    {
        $this->artisan('dentissa:probar-recordatorio', ['cita' => $this->cita->id, '--confirmar' => true])
            ->expectsOutputToContain('Recordatorio enviado por: whatsapp y email.')
            ->assertSuccessful();

        Http::assertSent(fn (Request $r) => $r['template']['name'] === 'cita_recordatorio'
            && $r['to'] === '5215512345678'
            && collect($r['template']['components'])->where('type', 'button')->count() === 3);
        Mail::assertSent(RecordatorioCita::class, 1);
        // Marcada como enviada: el envío programado no la repite.
        $this->assertNotNull($this->cita->fresh()->recordatorio_enviado_en);
    }

    public function test_si_whatsapp_falla_lo_dice_y_sugiere_revisar_la_plantilla(): void
    {
        Http::swap(new Factory);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['code' => 132001, 'message' => 'Template name does not exist']], 404)]);

        $this->artisan('dentissa:probar-recordatorio', ['cita' => $this->cita->id, '--confirmar' => true])
            ->expectsOutputToContain('Recordatorio enviado por: email.')
            ->expectsOutputToContain('plantilla cita_recordatorio esté aprobada')
            ->assertSuccessful();
    }

    public function test_rechaza_citas_inexistentes_canceladas_o_pasadas(): void
    {
        $this->artisan('dentissa:probar-recordatorio', ['cita' => 9999, '--confirmar' => true])->assertFailed();

        $this->cita->update(['estado' => EstadoCita::Cancelada]);
        $this->artisan('dentissa:probar-recordatorio', ['cita' => $this->cita->id, '--confirmar' => true])
            ->expectsOutputToContain('no admite recordatorio')
            ->assertFailed();

        $this->cita->update(['estado' => EstadoCita::Confirmada, 'fecha_hora' => Carbon::parse('2030-01-08 10:00')]);
        $this->artisan('dentissa:probar-recordatorio', ['cita' => $this->cita->id, '--confirmar' => true])->assertFailed();

        Http::assertNothingSent();
        Mail::assertNothingSent();
    }
}

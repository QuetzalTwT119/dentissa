<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Services\AgendaTelegramService;
use App\Services\CrearCitaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Fase 1 para usar Dentissa con pacientes reales: aviso de privacidad y
 * consentimiento expreso antes de guardar datos personales y de salud.
 */
class AvisoPrivacidadTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;

    private Tratamiento $tratamiento;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $this->tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'categoria' => 'Preventiva', 'activo' => true]);
        $this->servicio->tratamientos()->attach($this->tratamiento);
    }

    public function test_el_aviso_es_publico_y_tiene_los_apartados_que_pide_la_ley(): void
    {
        $this->get(route('privacidad.aviso'))
            ->assertOk()
            ->assertSee('Aviso de privacidad integral')
            ->assertSee('Responsable de tus datos')
            ->assertSee('Datos de salud (datos sensibles)')
            ->assertSee('Para qué usamos tus datos')
            ->assertSee('Con quién se comparten')
            ->assertSee('derechos ARCO')
            ->assertSee('Versión '.config('dentissa.privacidad.version'));
    }

    public function test_mientras_falten_datos_o_revision_el_aviso_lo_dice_claramente(): void
    {
        config(['dentissa.privacidad' => ['version' => '2026-11-16', 'revisado' => false, 'responsable' => null, 'domicilio' => null, 'correo' => null]]);

        $this->get(route('privacidad.aviso'))
            ->assertOk()
            ->assertSee('Borrador pendiente de revisión legal.')
            ->assertSee('[Por completar: nombre del responsable]')
            ->assertSee('[Por completar: domicilio]')
            ->assertSee('[Por completar: correo para solicitudes]');
    }

    public function test_con_los_datos_de_la_clinica_y_revisado_el_aviso_queda_limpio(): void
    {
        config(['dentissa.privacidad' => [
            'version' => '2026-11-16',
            'revisado' => true,
            'responsable' => 'Clínica de Prueba S.C.',
            'domicilio' => 'Calle Falsa 123, Zihuatanejo, Gro.',
            'correo' => 'privacidad@example.test',
        ]]);

        $this->get(route('privacidad.aviso'))
            ->assertOk()
            ->assertSee('Clínica de Prueba S.C.')
            ->assertSee('href="mailto:privacidad@example.test"', false)
            ->assertDontSee('Borrador pendiente')
            ->assertDontSee('Por completar');
    }

    public function test_la_agenda_no_guarda_nada_sin_aceptar_el_aviso(): void
    {
        $this->post(route('citas.publica.store'), $this->datos())
            ->assertSessionHasErrors(['acepta_aviso' => 'Para agendar necesitamos que aceptes el aviso de privacidad.']);

        $this->assertDatabaseCount('pacientes', 0);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_al_aceptar_se_guarda_la_fecha_y_la_version_del_aviso(): void
    {
        Carbon::setTestNow('2030-01-07 10:00');

        try {
            $this->post(route('citas.publica.store'), $this->datos() + ['acepta_aviso' => '1'])
                ->assertSessionHas('success');
        } finally {
            Carbon::setTestNow();
        }

        $paciente = Paciente::sole();
        $this->assertSame('2030-01-07 10:00:00', $paciente->aviso_privacidad_aceptado_en->format('Y-m-d H:i:s'));
        $this->assertSame(config('dentissa.privacidad.version'), $paciente->aviso_privacidad_version);
    }

    public function test_el_consentimiento_no_se_puede_inyectar_como_dato_del_paciente(): void
    {
        $this->assertNotContains('aviso_privacidad_aceptado_en', (new Paciente)->getFillable());
        $this->assertNotContains('aviso_privacidad_version', (new Paciente)->getFillable());
    }

    public function test_ningun_canal_puede_crear_una_cita_sin_consentimiento(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(CrearCitaService::class)->crear($this->datos(), 'telegram');
    }

    public function test_el_formulario_y_el_pie_enlazan_el_aviso(): void
    {
        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('name="acepta_aviso"', false)
            ->assertSee(route('privacidad.aviso'));

        $this->get('/')->assertOk()->assertSee(route('privacidad.aviso'));
    }

    public function test_telegram_pide_el_consentimiento_antes_de_confirmar(): void
    {
        $texto = (new \ReflectionMethod(AgendaTelegramService::class, 'prepararConfirmacion'))
            ->invoke(app(AgendaTelegramService::class), 99, [
                'servicio_id' => $this->servicio->id,
                'tratamiento_id' => $this->tratamiento->id,
                'fecha' => '2030-01-08',
                'hora' => '10:00',
                'nombre' => 'Ana',
                'apellido' => 'Prueba',
                'telefono' => '5512345678',
                'email' => 'ana@example.test',
            ], null);

        $this->assertStringContainsString('aceptas el aviso de privacidad ('.route('privacidad.aviso').')', $texto);
    }

    /** @return array<string, mixed> */
    private function datos(): array
    {
        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        return [
            'id_servicio' => $this->servicio->id,
            'id_tratamiento' => $this->tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '10:00',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5512345678',
            'email' => 'ana@example.test',
        ];
    }
}

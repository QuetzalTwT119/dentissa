<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Events\NotificacionSistemaCreada;
use App\Models\Cita;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\NotificacionInternaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Panel de la campana "Por atender" con avatares: los avisos que piden una
 * acción van arriba con botones; "Ya lo atendí" los pasa a informativas.
 */
class NotificacionesPorAtenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $asistente;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([NotificacionSistemaCreada::class]);
        Carbon::setTestNow('2030-01-09 10:00:00');

        $this->admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        // 22/11/2026: el otro miembro del personal con notificaciones ahora es doctor.
        $this->asistente = User::factory()->create(['rol' => RolUsuario::Doctor]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_el_aviso_de_cita_guarda_la_cita_para_mostrar_al_paciente(): void
    {
        $cita = $this->cita('Pedro', 'Salinas', '55 2219 6671');

        app(NotificacionInternaService::class)->cambioCita($cita, 'reagendar_paciente');

        $aviso = NotificacionSistema::where('user_id', $this->asistente->id)->firstOrFail();
        $this->assertSame($cita->id, $aviso->id_cita);
        $this->assertSame('Pedro Salinas', $aviso->persona());
        $this->assertSame('PS', $aviso->iniciales());
        $this->assertTrue($aviso->requiereAccion());
    }

    public function test_lo_prioritario_aparece_en_por_atender_con_avatar_y_acciones(): void
    {
        $cita = $this->cita('Pedro', 'Salinas', '55 2219 6671', '2030-01-11 10:30:00');
        $this->aviso('reagendar_paciente', 'Paciente pidió reagendar', $cita);

        $this->actingAs($this->admin)
            ->get(route('citas.index'))
            ->assertOk()
            ->assertSee('Por atender')
            ->assertSee('1 pendiente')
            ->assertSee('data-notification-task class', false)
            ->assertSee('aviso-avatar', false)
            ->assertSee('>PS<', false)
            ->assertSee('Pedro Salinas')
            ->assertSee('href="/citas?fecha=2030-01-11"', false)
            ->assertSee('href="tel:5522196671"', false)
            ->assertSee('Ya lo atendí');
    }

    public function test_lo_informativo_va_plegado_y_sin_botones(): void
    {
        $cita = $this->cita('Lucía', 'Hernández', '5511112222');
        $this->aviso('asistencia_confirmada', 'Paciente confirmó asistencia', $cita);

        $respuesta = $this->actingAs($this->admin)->get(route('citas.index'))->assertOk();

        $respuesta->assertSee('No hay nada pendiente.')
            ->assertSee('Informativas')
            ->assertSee('Paciente confirmó asistencia')
            ->assertSee('>LH<', false)
            ->assertDontSee('data-notification-task class', false)
            ->assertDontSee('Ya lo atendí');
    }

    public function test_un_aviso_del_sistema_usa_figura_sin_persona(): void
    {
        $this->aviso('respaldo_fallido', 'El respaldo no se completó');

        $this->actingAs($this->admin)
            ->get(route('citas.index'))
            ->assertOk()
            ->assertSee('aviso-sistema', false)
            ->assertSee('Ver detalle')
            ->assertDontSee('Llamar');
    }

    public function test_ya_lo_atendi_lo_pasa_a_informativas(): void
    {
        $cita = $this->cita('Valeria', 'Castro', '5533334444');
        $aviso = $this->aviso('cancelada_paciente', 'Paciente canceló su cita', $cita);

        $this->actingAs($this->admin)
            ->patchJson(route('notificaciones.atendida', $aviso))
            ->assertOk()
            ->assertJsonPath('por_atender', 0)
            ->assertJsonPath('no_vistas', 0)
            ->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, 'Paciente canceló su cita') && ! str_contains($html, 'Ya lo atendí'))->etc());

        $aviso->refresh();
        $this->assertNotNull($aviso->atendida_en);
        $this->assertNotNull($aviso->leida_en);
        $this->assertFalse($aviso->requiereAccion());

        $this->actingAs($this->admin)
            ->get(route('citas.index'))
            ->assertSee('No hay nada pendiente.')
            ->assertSee('Paciente canceló su cita')
            ->assertDontSee('Ya lo atendí');
    }

    public function test_no_se_puede_atender_un_aviso_ajeno_ni_uno_informativo(): void
    {
        $ajeno = NotificacionSistema::create([
            'user_id' => $this->asistente->id, 'evento' => 'no_asistio', 'titulo' => 'Ajeno', 'mensaje' => 'x',
        ]);
        $informativo = $this->aviso('agendada', 'Nueva cita registrada');

        $this->actingAs($this->admin)->patchJson(route('notificaciones.atendida', $ajeno))->assertNotFound();
        $this->actingAs($this->admin)->patchJson(route('notificaciones.atendida', $informativo))->assertStatus(422);
        $this->assertNull($ajeno->fresh()->atendida_en);
    }

    public function test_la_actualizacion_en_vivo_manda_el_aviso_armado_y_su_seccion(): void
    {
        $cita = $this->cita('Jorge', 'Ramírez', '5544445555');
        $this->aviso('no_asistio', 'Inasistencia registrada', $cita);

        $this->actingAs($this->admin)
            ->getJson(route('notificaciones.actualizaciones'))
            ->assertOk()
            ->assertJsonPath('notificaciones.0.por_atender', true)
            ->assertJson(fn ($json) => $json->where('notificaciones.0.html', fn ($html) => str_contains($html, '>JR<') && str_contains($html, 'Ya lo atendí'))->etc());
    }

    public function test_el_mismo_paciente_conserva_su_color_de_avatar(): void
    {
        $cita = $this->cita('Ana Sofía', 'Mendoza', '5500001111');
        $uno = $this->aviso('agendada', 'Nueva cita registrada', $cita);
        $dos = $this->aviso('confirmada', 'Cita confirmada', $cita);

        $this->assertSame('AS', $uno->iniciales());
        $this->assertSame($uno->tonoAvatar(), $dos->tonoAvatar());
        $this->assertContains($uno->tonoAvatar(), range(0, 5));
    }

    private function aviso(string $evento, string $titulo, ?Cita $cita = null): NotificacionSistema
    {
        return NotificacionSistema::create([
            'user_id' => $this->admin->id,
            'id_cita' => $cita?->id,
            'evento' => $evento,
            'titulo' => $titulo,
            'mensaje' => 'Detalle del aviso.',
            'url' => route('citas.index', [], false),
            'vista_en' => now(),
        ]);
    }

    private function cita(string $nombre, string $apellido, string $telefono, string $fechaHora = '2030-01-10 09:00:00'): Cita
    {
        $paciente = Paciente::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'email' => fake()->unique()->safeEmail(),
        ]);

        return Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => Servicio::firstOrCreate(['nombre' => 'Odontología General'], ['orden' => 1, 'activo' => true])->id,
            'id_tratamiento' => Tratamiento::firstOrCreate(['nombre' => 'Limpieza Dental'], ['duracion_estimada' => 60, 'activo' => true])->id,
            'fecha_hora' => Carbon::parse($fechaHora),
            'duracion_estimada' => 60,
            'estado' => EstadoCita::Agendada,
        ]);
    }
}

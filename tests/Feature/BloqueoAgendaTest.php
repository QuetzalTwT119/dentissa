<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\DisponibilidadService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Días u horas bloqueados (24/11/2026): vacaciones, imprevistos o
 * mantenimiento. Lo que se bloquea deja de ofrecerse en todos lados y el
 * servidor lo rechaza aunque alguien lo intente a mano.
 */
class BloqueoAgendaTest extends TestCase
{
    use RefreshDatabase;

    private DisponibilidadService $servicio;

    private Tratamiento $tratamiento;

    private User $doctora;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-27 07:00:00'); // viernes
        HorarioAtencion::query()->delete();
        foreach (range(0, 6) as $dia) {
            HorarioAtencion::create(['dia_semana' => $dia, 'abierto' => $dia !== 0, 'hora_apertura' => 9, 'hora_cierre' => 18, 'capacidad_simultanea' => 1]);
        }
        $this->servicio = app(DisponibilidadService::class);
        $this->tratamiento = Tratamiento::create(['nombre' => 'Limpieza', 'duracion_estimada' => 30, 'activo' => true, 'precio' => 500]);
        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function bloquear(array $datos)
    {
        return $this->actingAs($this->doctora)->from(route('admin.configuracion'))->post(route('admin.bloqueos.store'), $datos);
    }

    public function test_un_dia_completo_deja_de_ofrecerse_y_el_calendario_lo_muestra_cerrado(): void
    {
        $this->bloquear(['fecha_inicio' => '2026-12-24', 'fecha_fin' => '2026-12-26', 'todo_el_dia' => '1', 'motivo' => 'Vacaciones'])
            ->assertRedirect(route('admin.configuracion').'#bloqueos')
            ->assertSessionHas('success');

        foreach (['2026-12-24', '2026-12-25', '2026-12-26'] as $fecha) {
            $this->assertSame([], $this->servicio->horariosDisponibles($fecha, $this->tratamiento), $fecha);
        }
        $this->assertNotEmpty($this->servicio->horariosDisponibles('2026-12-23', $this->tratamiento));
        $this->assertNotEmpty($this->servicio->horariosDisponibles('2026-12-28', $this->tratamiento));

        $mes = $this->servicio->resumenMensual(CarbonImmutable::parse('2026-12-01'), $this->tratamiento);
        $this->assertSame('cerrado', $mes['2026-12-24']['estado']);
        $this->assertSame('cerrado', $mes['2026-12-26']['estado']);
        $this->assertSame('disponible', $mes['2026-12-23']['estado']);

        // El servidor tampoco acepta una cita ahí.
        $this->assertFalse($this->servicio->estaDisponible(Carbon::parse('2026-12-24 10:00'), $this->tratamiento));
    }

    public function test_unas_horas_solo_quitan_ese_tramo(): void
    {
        $this->bloquear(['fecha_inicio' => '2026-12-01', 'hora_inicio' => '10:00', 'hora_fin' => '12:00', 'motivo' => 'Mantenimiento del sillón'])
            ->assertSessionHasNoErrors();

        $libres = $this->servicio->horariosDisponibles('2026-12-01', $this->tratamiento);
        $this->assertContains('09:30', $libres);   // termina justo a las 10:00
        $this->assertNotContains('10:00', $libres);
        $this->assertNotContains('11:30', $libres);
        $this->assertContains('12:00', $libres);

        // Una cita de 60 min a las 9:30 invadiría el bloqueo.
        $larga = Tratamiento::create(['nombre' => 'Larga', 'duracion_estimada' => 60, 'activo' => true, 'precio' => 900]);
        $this->assertNotContains('09:30', $this->servicio->horariosDisponibles('2026-12-01', $larga));
        $this->assertFalse($this->servicio->estaDisponible(Carbon::parse('2026-12-01 11:00'), $this->tratamiento));
        $this->assertTrue($this->servicio->estaDisponible(Carbon::parse('2026-12-01 12:00'), $this->tratamiento));
    }

    public function test_no_deja_bloquear_un_tiempo_con_citas_activas(): void
    {
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5511112222']);
        Cita::create(['id_paciente' => $paciente->id, 'id_tratamiento' => $this->tratamiento->id, 'fecha_hora' => '2026-12-02 10:30', 'duracion_estimada' => 30, 'estado' => 'confirmada']);
        Cita::create(['id_paciente' => $paciente->id, 'id_tratamiento' => $this->tratamiento->id, 'fecha_hora' => '2026-12-02 15:00', 'duracion_estimada' => 30, 'estado' => 'cancelada']);

        $this->bloquear(['fecha_inicio' => '2026-12-02', 'todo_el_dia' => '1', 'motivo' => 'Curso'])
            ->assertSessionHasErrors(['fecha_inicio' => 'Hay 1 cita activa en ese tiempo (2 dic., 10:30). Reagéndalas o cancélalas en la Agenda antes de bloquear.']);
        $this->bloquear(['fecha_inicio' => '2026-12-02', 'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'motivo' => 'Curso'])
            ->assertSessionHasErrors('fecha_inicio');

        // Las horas sin citas (y las canceladas) sí se pueden bloquear.
        $this->bloquear(['fecha_inicio' => '2026-12-02', 'hora_inicio' => '14:00', 'hora_fin' => '16:00', 'motivo' => 'Curso'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, BloqueoAgenda::count());
    }

    public function test_valida_fechas_horas_y_motivo(): void
    {
        $this->bloquear(['fecha_inicio' => '2026-11-20', 'todo_el_dia' => '1', 'motivo' => 'x'])->assertSessionHasErrors('fecha_inicio');
        $this->bloquear(['fecha_inicio' => '2026-12-05', 'fecha_fin' => '2026-12-04', 'todo_el_dia' => '1', 'motivo' => 'x'])->assertSessionHasErrors('fecha_fin');
        $this->bloquear(['fecha_inicio' => '2026-12-05', 'hora_inicio' => '12:00', 'hora_fin' => '11:00', 'motivo' => 'x'])->assertSessionHasErrors('hora_fin');
        $this->bloquear(['fecha_inicio' => '2026-12-05', 'motivo' => 'x'])->assertSessionHasErrors(['hora_inicio', 'hora_fin']);
        $this->bloquear(['fecha_inicio' => '2026-12-05', 'fecha_fin' => '2026-12-06', 'hora_inicio' => '10:00', 'hora_fin' => '11:00', 'motivo' => 'x'])->assertSessionHasErrors('fecha_fin');
        $this->bloquear(['fecha_inicio' => '2026-12-05', 'fecha_fin' => '2027-03-05', 'todo_el_dia' => '1', 'motivo' => 'x'])->assertSessionHasErrors('fecha_fin');
        $this->bloquear(['fecha_inicio' => '2026-12-05', 'todo_el_dia' => '1'])->assertSessionHasErrors('motivo');
        $this->assertSame(0, BloqueoAgenda::count());
    }

    public function test_configuracion_lista_los_bloqueos_y_se_pueden_quitar_con_registro(): void
    {
        $this->bloquear(['fecha_inicio' => '2026-12-31', 'todo_el_dia' => '1', 'motivo' => 'Año nuevo']);
        $bloqueo = BloqueoAgenda::firstOrFail();

        $this->actingAs($this->doctora)->get(route('admin.configuracion'))->assertOk()
            ->assertSee('Días u horas bloqueados')
            ->assertSee('Año nuevo')
            ->assertSee('todo el día')
            ->assertSee(route('admin.bloqueos.destroy', $bloqueo), false);

        $this->actingAs($this->doctora)->delete(route('admin.bloqueos.destroy', $bloqueo))->assertRedirect(route('admin.configuracion').'#bloqueos');
        $this->assertSame(0, BloqueoAgenda::count());
        $this->assertNotEmpty($this->servicio->horariosDisponibles('2026-12-31', $this->tratamiento));

        $this->assertSame(['bloqueo.creado', 'bloqueo.eliminado'], LogAuditoria::whereIn('accion', ['bloqueo.creado', 'bloqueo.eliminado'])->orderBy('id')->pluck('accion')->all());
    }

    public function test_solo_quien_tiene_configuracion_puede_bloquear(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)->post(route('admin.bloqueos.store'), ['fecha_inicio' => '2026-12-31', 'todo_el_dia' => '1', 'motivo' => 'x'])->assertForbidden();
        $this->assertSame(0, BloqueoAgenda::count());
    }
}

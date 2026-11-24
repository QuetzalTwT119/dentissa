<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vista semanal de la agenda (RF03). La semana fija del 7 al 12 de enero de
 * 2030 (lunes a sábado) hace que las pruebas no dependan del día en que corren.
 */
class AgendaSemanalTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;

    private Tratamiento $tratamiento;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2030-01-09 10:00:00'); // miércoles

        $this->servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $this->tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);
        $this->servicio->tratamientos()->attach($this->tratamiento->id, ['orden' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_solo_el_personal_autenticado_ve_la_semana_y_el_selector_de_vista(): void
    {
        $this->get(route('citas.index', ['vista' => 'semana']))->assertRedirect(route('login'));

        // 24/11/2026: la asistente vuelve a tener la Agenda (config/permisos.php).
        foreach ([RolUsuario::SuperAdmin, RolUsuario::Doctor, RolUsuario::Asistente] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]))
                ->get(route('citas.index', ['vista' => 'semana']))
                ->assertOk()
                ->assertSee('data-vista-actual="semana"', false)
                ->assertSee(route('citas.index'), false)
                ->assertSee(route('citas.index', ['vista' => 'semana']), false);
        }
    }

    public function test_muestra_solo_las_citas_de_la_semana_ordenadas_por_hora(): void
    {
        $this->cita('Ana', 'Torres', '2030-01-07 11:00');
        $this->cita('Beto', 'Ruiz', '2030-01-07 09:00');
        $this->cita('Fuera', 'Antes', '2030-01-05 09:00');   // sábado de la semana anterior
        $this->cita('Fuera', 'Despues', '2030-01-14 09:00'); // lunes de la semana siguiente

        $this->actingAs($this->admin())
            ->get(route('citas.index', ['vista' => 'semana', 'semana' => '2030-01-07']))
            ->assertOk()
            ->assertSeeInOrder(['Beto Ruiz', 'Ana Torres'])
            ->assertDontSee('Fuera Antes')
            ->assertDontSee('Fuera Despues');
    }

    public function test_cada_cita_muestra_hora_paciente_tratamiento_estado_y_enlace(): void
    {
        $cita = $this->cita('Ana', 'Torres', '2030-01-08 09:30', EstadoCita::Confirmada);

        $this->actingAs($this->admin())
            ->get(route('citas.index', ['vista' => 'semana', 'semana' => '2030-01-07']))
            ->assertOk()
            ->assertSeeInOrder(['09:30', 'Confirmada', 'Ana Torres', 'Limpieza Dental'])
            ->assertSee(route('citas.edit', $cita), false);
    }

    public function test_navega_entre_semanas_y_normaliza_cualquier_dia_al_lunes(): void
    {
        $respuesta = $this->actingAs($this->admin())
            ->get(route('citas.index', ['vista' => 'semana', 'semana' => '2030-01-10'])) // jueves
            ->assertOk()
            ->assertSee('data-semana="2030-01-07"', false);

        $respuesta->assertSee(route('citas.index', ['vista' => 'semana', 'semana' => '2029-12-31']))
            ->assertSee(route('citas.index', ['vista' => 'semana', 'semana' => '2030-01-14']));
    }

    public function test_una_semana_invalida_muestra_la_semana_actual_y_marca_hoy(): void
    {
        foreach (['no-es-fecha', '2030-02-30', ''] as $valor) {
            $this->actingAs($this->admin())
                ->get(route('citas.index', ['vista' => 'semana', 'semana' => $valor]))
                ->assertOk()
                ->assertSee('data-semana="2030-01-07"', false)
                ->assertSee('data-dia="2030-01-09" data-hoy', false);
        }
    }

    public function test_los_dias_cerrados_se_muestran_como_cerrados_segun_el_horario(): void
    {
        // La migración crea los 7 días (lunes a viernes 8–18, sábado 9–14, domingo cerrado).
        HorarioAtencion::where('dia_semana', 6)->update(['abierto' => false]);

        $this->actingAs($this->admin())
            ->get(route('citas.index', ['vista' => 'semana', 'semana' => '2030-01-07']))
            ->assertOk()
            ->assertSee('data-dia="2030-01-11"', false)
            ->assertSeeInOrder(['data-dia="2030-01-12"', 'Cerrado'], false)
            ->assertSee('8:00 – 18:00')
            ->assertDontSee('data-dia="2030-01-13"', false); // domingo cerrado y sin citas
    }

    public function test_la_vista_de_lista_sigue_siendo_la_predeterminada(): void
    {
        $this->cita('Ana', 'Torres', '2030-01-08 09:30');

        $this->actingAs($this->admin())
            ->get(route('citas.index', ['fecha' => '2030-01-08']))
            ->assertOk()
            ->assertSee('data-vista-actual="lista"', false)
            ->assertSee('Ana Torres')
            ->assertDontSee('data-semana=', false);
    }

    private function admin(): User
    {
        return User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    private function cita(string $nombre, string $apellido, string $fechaHora, EstadoCita $estado = EstadoCita::Agendada): Cita
    {
        $paciente = Paciente::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => '55'.random_int(10000000, 99999999),
            'email' => strtolower($nombre.'.'.$apellido).random_int(1, 9999).'@example.test',
        ]);

        return Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $this->servicio->id,
            'id_tratamiento' => $this->tratamiento->id,
            'fecha_hora' => Carbon::parse($fechaHora),
            'duracion_estimada' => 60,
            'estado' => $estado,
        ]);
    }
}

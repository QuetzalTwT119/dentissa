<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Calendario de la agenda: GET /disponibilidad/mes resume cuántos horarios
 * libres tiene cada día del mes y por qué un día no tiene ninguno.
 */
class DisponibilidadMesTest extends TestCase
{
    use RefreshDatabase;

    private Tratamiento $tratamiento;

    protected function setUp(): void
    {
        parent::setUp();

        // Miércoles 9 de enero de 2030, 10:00 en la clínica.
        Carbon::setTestNow(Carbon::parse('2030-01-09 10:00:00', 'America/Mexico_City'));

        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $this->tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($this->tratamiento);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_resume_cada_dia_del_mes_con_su_estado(): void
    {
        $respuesta = $this->getJson($this->ruta(['mes' => '2030-01']))
            ->assertOk()
            ->assertJsonPath('mes', '2030-01')
            ->assertJsonPath('duracion_minutos', 60)
            ->assertJsonCount(31, 'dias');

        $dias = $respuesta->json('dias');

        $this->assertSame(['horarios' => 0, 'estado' => 'pasado'], $dias['2030-01-08']);
        // Domingo: el consultorio no abre (valor por defecto de horarios_atencion).
        $this->assertSame(['horarios' => 0, 'estado' => 'cerrado'], $dias['2030-01-13']);
        $this->assertSame('disponible', $dias['2030-01-09']['estado']);
        $this->assertSame('disponible', $dias['2030-01-14']['estado']);
    }

    public function test_el_numero_de_cada_dia_coincide_con_la_lista_que_vera_el_paciente(): void
    {
        $dias = $this->getJson($this->ruta(['mes' => '2030-01']))->assertOk()->json('dias');
        $servicio = app(DisponibilidadService::class);

        // Hoy ya no cuenta los horarios que pasaron; un sábado tiene horario corto.
        foreach (['2030-01-09', '2030-01-12', '2030-01-14'] as $fecha) {
            $this->assertSame(
                count($servicio->horariosDisponibles($fecha, $this->tratamiento)),
                $dias[$fecha]['horarios'],
                "El calendario y la lista de horarios no coinciden el {$fecha}.",
            );
        }
    }

    public function test_un_dia_sin_cupo_se_distingue_de_un_dia_cerrado(): void
    {
        // Capacidad simultánea 2: dos citas de 8:00 a 18:00 llenan el jueves.
        $this->crearCita('2030-01-10 08:00', 600);
        $this->crearCita('2030-01-10 08:00', 600);

        $dias = $this->getJson($this->ruta(['mes' => '2030-01']))->assertOk()->json('dias');

        $this->assertSame(['horarios' => 0, 'estado' => 'sin_horarios'], $dias['2030-01-10']);
    }

    public function test_solo_acepta_del_mes_actual_a_doce_meses_adelante(): void
    {
        $this->getJson($this->ruta(['mes' => '2029-12']))->assertUnprocessable()->assertJsonValidationErrors('mes');
        $this->getJson($this->ruta(['mes' => '2031-02']))->assertUnprocessable()->assertJsonValidationErrors('mes');
        $this->getJson($this->ruta(['mes' => '2031-01']))->assertOk();
        $this->getJson($this->ruta(['mes' => '2030-1']))->assertUnprocessable()->assertJsonValidationErrors('mes');
        $this->getJson($this->ruta(['mes' => 'enero']))->assertUnprocessable()->assertJsonValidationErrors('mes');
    }

    public function test_rechaza_un_tratamiento_inactivo(): void
    {
        $this->tratamiento->update(['activo' => false]);

        $this->getJson($this->ruta(['mes' => '2030-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tratamiento_id');
    }

    public function test_la_agenda_publica_no_puede_excluir_una_cita_del_calculo(): void
    {
        $cita = $this->crearCita('2030-01-10 10:00', 60);

        $this->getJson($this->ruta(['mes' => '2030-01', 'exclude_cita_id' => $cita->id]))->assertForbidden();
    }

    public function test_el_personal_que_reagenda_ve_libre_el_horario_de_la_cita_que_mueve(): void
    {
        $this->crearCita('2030-01-10 08:00', 600);
        $cita = $this->crearCita('2030-01-10 08:00', 600);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $dias = $this->actingAs($asistente)
            ->getJson($this->ruta(['mes' => '2030-01', 'exclude_cita_id' => $cita->id]))
            ->assertOk()
            ->json('dias');

        $this->assertSame('disponible', $dias['2030-01-10']['estado']);
    }

    public function test_reagendar_usa_el_calendario_sin_contar_la_cita_que_se_mueve(): void
    {
        $cita = $this->crearCita('2030-01-10 10:00', 60);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->get(route('citas.edit', $cita))
            ->assertOk()
            ->assertSee('calendarioDisponibilidad(', false)
            ->assertSee("excluirCitaId: {$cita->id}", false)
            ->assertSee('<input type="hidden" name="fecha" x-model="fecha">', false)
            ->assertDontSee('type="date"', false);
    }

    private function ruta(array $parametros): string
    {
        return route('disponibilidad.mes', ['tratamiento_id' => $this->tratamiento->id, ...$parametros]);
    }

    private function crearCita(string $fechaHora, int $duracion): Cita
    {
        $paciente = Paciente::create([
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5512345678',
            'email' => fake()->unique()->safeEmail(),
        ]);

        return Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => Servicio::first()->id,
            'id_tratamiento' => $this->tratamiento->id,
            'fecha_hora' => Carbon::parse($fechaHora),
            'duracion_estimada' => $duracion,
        ]);
    }
}

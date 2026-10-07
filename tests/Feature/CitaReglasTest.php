<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CitaReglasTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_se_puede_agendar_un_tratamiento_que_no_pertenece_al_servicio(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente();
        $servicioElegido = Servicio::create(['nombre' => 'Servicio elegido', 'activo' => true]);
        $otroServicio = Servicio::create(['nombre' => 'Otro servicio', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento de otro servicio',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $otroServicio->tratamientos()->attach($tratamiento);

        $this->actingAs($admin)
            ->post(route('citas.store'), [
                'id_paciente' => $paciente->id,
                'id_servicio' => $servicioElegido->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha' => $this->fechaFutura()->toDateString(),
                'hora' => '10:00',
            ])
            ->assertSessionHasErrors('id_tratamiento');

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_no_se_puede_agendar_un_servicio_inactivo(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente();
        $servicio = Servicio::create(['nombre' => 'Servicio inactivo', 'activo' => false]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento valido',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $this->actingAs($admin)
            ->post(route('citas.store'), [
                'id_paciente' => $paciente->id,
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha' => $this->fechaFutura()->toDateString(),
                'hora' => '10:00',
            ])
            ->assertSessionHasErrors('id_servicio');

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_el_agendamiento_publico_rechaza_un_tratamiento_inactivo(): void
    {
        $servicio = Servicio::create(['nombre' => 'Servicio publico', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento inactivo',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => false,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $this->post(route('citas.publica.store'), [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $this->fechaFutura()->toDateString(),
            'hora' => '10:00',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5555555555',
            'email' => 'ana-reglas@example.test',
        ])->assertSessionHasErrors('id_tratamiento');

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_el_agendamiento_publico_rechaza_una_fecha_pasada(): void
    {
        $servicio = Servicio::create(['nombre' => 'Servicio fecha', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento fecha',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $this->post(route('citas.publica.store'), [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $this->fechaPasada()->toDateString(),
            'hora' => '10:00',
            'nombre' => 'Ana',
            'apellido' => 'Pasada',
            'telefono' => '5555555555',
            'email' => 'ana-pasada@example.test',
        ])->assertSessionHasErrors('hora');

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_el_personal_puede_iniciar_y_completar_una_cita_confirmada(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $paciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => $this->fechaFutura()->setTime(10, 0),
            'duracion_estimada' => 30,
            'estado' => EstadoCita::Confirmada,
        ]);

        $this->actingAs($asistente)
            ->patch(route('citas.start', $cita))
            ->assertRedirect(route('citas.index'));

        $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado' => EstadoCita::EnCurso->value]);

        $this->actingAs($asistente)
            ->patch(route('citas.complete', $cita))
            ->assertRedirect(route('citas.index'));

        $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado' => EstadoCita::Completada->value]);
    }

    public function test_una_cita_confirmada_puede_marcarse_como_no_asistio(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => $this->fechaFutura()->setTime(10, 0),
            'duracion_estimada' => 30,
            'estado' => EstadoCita::Confirmada,
        ]);

        $this->actingAs($admin)
            ->patch(route('citas.no-show', $cita))
            ->assertRedirect(route('citas.index'));

        $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado' => EstadoCita::NoAsistio->value]);
    }

    private function crearPaciente(): Paciente
    {
        return Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'QA',
            'telefono' => '5555555555',
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    /** @return array{0: Servicio, 1: Tratamiento} */
    private function crearCatalogo(): array
    {
        $servicio = Servicio::create(['nombre' => 'Servicio QA', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento QA',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        return [$servicio, $tratamiento];
    }

    private function fechaFutura(): Carbon
    {
        $fecha = Carbon::tomorrow()->startOfDay();

        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        return $fecha;
    }

    private function fechaPasada(): Carbon
    {
        $fecha = Carbon::yesterday()->startOfDay();

        while ($fecha->isSunday()) {
            $fecha->subDay();
        }

        return $fecha;
    }
}

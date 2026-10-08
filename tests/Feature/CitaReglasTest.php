<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
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

    public function test_el_personal_puede_crear_una_cita_interna_valida(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $fecha = $this->fechaFutura();

        $this->actingAs($admin)
            ->post(route('citas.store'), [
                'id_paciente' => $paciente->id,
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha' => $fecha->toDateString(),
                'hora' => '10:00',
                'motivo' => 'Consulta interna de QA',
            ])
            ->assertRedirect(route('citas.index'));

        $this->assertDatabaseHas('citas', [
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'estado' => EstadoCita::Agendada->value,
            'motivo' => 'Consulta interna de QA',
        ]);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $admin->id,
            'accion' => 'cita.creada',
            'tabla' => 'citas',
            'registro_id' => Cita::query()->latest('id')->value('id'),
        ]);
    }

    public function test_no_se_supera_la_capacidad_simultanea_para_un_mismo_horario(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $primerPaciente = $this->crearPaciente();
        $segundoPaciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $fecha = $this->fechaFutura();

        HorarioAtencion::where('dia_semana', $fecha->dayOfWeek)->update([
            'abierto' => true,
            'hora_apertura' => 8,
            'hora_cierre' => 18,
            'capacidad_simultanea' => 1,
        ]);

        $datosCita = [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '10:00',
        ];

        $this->actingAs($admin)
            ->post(route('citas.store'), $datosCita + ['id_paciente' => $primerPaciente->id])
            ->assertRedirect(route('citas.index'));

        $this->actingAs($admin)
            ->post(route('citas.store'), $datosCita + ['id_paciente' => $segundoPaciente->id])
            ->assertSessionHasErrors('hora');

        $this->assertDatabaseCount('citas', 1);
    }

    public function test_solo_el_personal_puede_autorizar_la_creacion_interna(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->assertTrue(Gate::forUser($admin)->allows('create', Cita::class));
        $this->assertTrue(Gate::forUser($asistente)->allows('create', Cita::class));
    }

    public function test_una_cita_agendada_puede_confirmarse(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => $this->fechaFutura()->setTime(10, 0),
            'duracion_estimada' => $tratamiento->duracion_estimada,
            'estado' => EstadoCita::Agendada,
        ]);

        $this->actingAs($admin)
            ->patch(route('citas.confirm', $cita))
            ->assertRedirect(route('citas.index'));

        $cita->refresh();

        $this->assertSame(EstadoCita::Confirmada, $cita->estado);
        $this->assertSame($admin->id, $cita->confirmada_por);
        $this->assertNotNull($cita->fecha_confirmacion);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $admin->id,
            'accion' => 'cita.confirmada',
            'tabla' => 'citas',
            'registro_id' => $cita->id,
        ]);
    }

    public function test_una_cita_agendada_puede_cancelarse_con_motivo(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => $this->fechaFutura()->setTime(10, 0),
            'duracion_estimada' => $tratamiento->duracion_estimada,
            'estado' => EstadoCita::Agendada,
        ]);

        $this->actingAs($admin)
            ->patch(route('citas.cancel', $cita), [
                'motivo_cancelacion' => 'Cancelación de prueba QA',
            ])
            ->assertRedirect(route('citas.index'));

        $cita->refresh();

        $this->assertSame(EstadoCita::Cancelada, $cita->estado);
        $this->assertSame($admin->id, $cita->cancelada_por);
        $this->assertSame('Cancelación de prueba QA', $cita->motivo_cancelacion);
        $this->assertNotNull($cita->fecha_cancelacion);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $admin->id,
            'accion' => 'cita.cancelada',
            'tabla' => 'citas',
            'registro_id' => $cita->id,
        ]);
    }

    public function test_una_cita_agendada_puede_reagendarse_a_otro_horario(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $paciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $fechaOriginal = $this->fechaFutura()->setTime(10, 0);
        $nuevaFecha = $this->fechaFutura()->addWeek()->setTime(11, 0);
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => $fechaOriginal,
            'duracion_estimada' => $tratamiento->duracion_estimada,
            'estado' => EstadoCita::Agendada,
        ]);

        $this->actingAs($asistente)
            ->put(route('citas.update', $cita), [
                'fecha' => $nuevaFecha->toDateString(),
                'hora' => '11:00',
            ])
            ->assertRedirect(route('citas.index'));

        $cita->refresh();

        $this->assertSame(EstadoCita::Agendada, $cita->estado);
        $this->assertTrue($cita->fecha_hora->equalTo($nuevaFecha));
        $this->assertNull($cita->confirmada_por);
        $this->assertNull($cita->fecha_confirmacion);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $asistente->id,
            'accion' => 'cita.reagendada',
            'tabla' => 'citas',
            'registro_id' => $cita->id,
        ]);
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

    public function test_las_transiciones_invalidas_no_cambian_el_estado_de_la_cita(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();

        $casos = [
            ['accion' => 'confirm', 'estado' => EstadoCita::Confirmada],
            ['accion' => 'cancel', 'estado' => EstadoCita::Completada],
            ['accion' => 'update', 'estado' => EstadoCita::Completada],
            ['accion' => 'start', 'estado' => EstadoCita::Agendada],
            ['accion' => 'no-show', 'estado' => EstadoCita::Agendada],
            ['accion' => 'complete', 'estado' => EstadoCita::Confirmada],
        ];

        foreach ($casos as $caso) {
            $cita = Cita::create([
                'id_paciente' => $paciente->id,
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha_hora' => $this->fechaFutura()->setTime(10, 0),
                'duracion_estimada' => $tratamiento->duracion_estimada,
                'estado' => $caso['estado'],
            ]);

            if ($caso['accion'] === 'update') {
                $respuesta = $this->actingAs($admin)->put(route('citas.update', $cita), [
                    'fecha' => $this->fechaFutura()->addWeek()->toDateString(),
                    'hora' => '11:00',
                ]);
            } else {
                $respuesta = $this->actingAs($admin)->patch(route('citas.'.$caso['accion'], $cita));
            }

            $respuesta->assertSessionHasErrors('estado');
            $this->assertSame($caso['estado'], $cita->fresh()->estado);
        }
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

<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CitaPublicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_horarios_se_calculan_con_la_hora_local_de_la_clinica(): void
    {
        $this->assertSame('America/Mexico_City', config('app.timezone'));

        Carbon::setTestNow(Carbon::parse('2026-10-17 10:00:00', 'America/Mexico_City'));

        try {
            $tratamiento = Tratamiento::create([
                'nombre' => 'Limpieza Dental',
                'duracion_estimada' => 60,
                'categoria' => 'Preventiva',
                'activo' => true,
            ]);

            $horarios = app(DisponibilidadService::class)
                ->horariosDisponibles('2026-10-17', $tratamiento);

            $this->assertContains('10:30', $horarios);
            $this->assertContains('13:00', $horarios);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_un_paciente_puede_agendar_una_cita_desde_el_formulario_publico(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('Odontología General')
            ->assertSee('Agendar cita');

        $respuesta = $this->post(route('citas.publica.store'), [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '09:00',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5555555555',
            'email' => 'ana.publica@example.test',
            'motivo' => 'Consulta de prueba',
        ]);

        $respuesta
            ->assertRedirect(route('citas.publica.create'))
            ->assertSessionHas('success');

        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('¡Listo! Tu cita quedó confirmada. Te esperamos.')
            ->assertSee('Volver al inicio');

        $paciente = Paciente::where('email', 'ana.publica@example.test')->firstOrFail();
        $this->assertSame('5555555555', $paciente->telefono);
        $cita = Cita::where('id_paciente', $paciente->id)->firstOrFail();

        $this->assertSame(EstadoCita::Confirmada, $cita->estado);
        $this->assertNull($cita->confirmada_por);
        $this->assertNotNull($cita->fecha_confirmacion);
        $this->assertSame(
            $fecha->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
            $cita->fecha_hora->format('Y-m-d H:i:s'),
        );
        $this->assertDatabaseHas('notificaciones', [
            'id_paciente' => $paciente->id,
            'id_cita' => $cita->id,
            'canal' => 'cita_confirmada',
        ]);

        $this->assertDatabaseMissing('logs_auditoria', [
            'tabla' => 'citas',
            'registro_id' => $cita->id,
            'accion' => 'cita.creada',
        ]);
    }

    public function test_el_formulario_publico_etiqueta_los_datos_requeridos_y_aclara_el_paso_de_resumen(): void
    {
        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('for="nombre"', false)
            ->assertSee('for="apellido"', false)
            ->assertSee('for="telefono"', false)
            ->assertSee('for="email"', false)
            ->assertSee('for="motivo"', false)
            ->assertSee('name="nombre" autocomplete="given-name" maxlength="100" required', false)
            ->assertSee('name="apellido" autocomplete="family-name" maxlength="100" required', false)
            ->assertSee('name="telefono" autocomplete="tel" inputmode="numeric" maxlength="10" pattern="[0-9]{10}"', false)
            ->assertSee('Ingresa exactamente 10 dígitos numéricos.', false)
            ->assertSee('name="email" autocomplete="email" maxlength="100" required', false)
            ->assertSee('Resumen de la cita')
            ->assertSee('Continuar a mis datos');
    }

    public function test_la_agenda_publica_rechaza_datos_de_contacto_vacios_sin_crear_registros(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        $this->post(route('citas.publica.store'), [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '09:00',
        ])
            ->assertSessionHasErrors(['nombre', 'apellido', 'telefono', 'email']);

        $this->assertDatabaseCount('pacientes', 0);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_la_agenda_publica_rechaza_telefonos_con_longitud_o_caracteres_invalidos(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        foreach (['123456789', '12345678901', '521234567890', '+5555555555', 'abc1234567', '7551131833333333333'] as $telefono) {
            $this->post(route('citas.publica.store'), [
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha' => $fecha->toDateString(),
                'hora' => '09:00',
                'nombre' => 'Ana',
                'apellido' => 'Prueba',
                'telefono' => $telefono,
                'email' => 'ana.publica@example.test',
            ])->assertSessionHasErrors('telefono');
        }

        $this->assertDatabaseCount('pacientes', 0);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_el_formulario_publico_muestra_el_progreso_y_limita_el_telefono_a_mexico(): void
    {
        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('Tu cita, paso a paso')
            ->assertSee('aria-label="Volver al inicio"', false)
            ->assertSee('inputmode="numeric" maxlength="10" pattern="[0-9]{10}"', false)
            ->assertSee('oninput=', false)
            ->assertSee('slice(0, 10)', false);
    }

    public function test_la_agenda_publica_rechaza_telefonos_que_no_sean_de_diez_digitos(): void
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $fecha = Carbon::tomorrow();
        while ($fecha->isSunday()) {
            $fecha->addDay();
        }

        foreach (['123456789', '12345678901', '521234567890', '+5555555555', 'abc1234567'] as $telefono) {
            $this->post(route('citas.publica.store'), [
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha' => $fecha->toDateString(),
                'hora' => '09:00',
                'nombre' => 'Ana',
                'apellido' => 'Prueba',
                'telefono' => $telefono,
                'email' => 'ana.publica@example.test',
            ])->assertSessionHasErrors('telefono');
        }

        $this->assertDatabaseCount('pacientes', 0);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_la_consulta_publica_no_puede_excluir_una_cita_existente_del_calculo_de_horarios(): void
    {
        $cita = $this->crearCitaExistente();

        $this->getJson(route('disponibilidad.index', [
            'tratamiento_id' => $cita->id_tratamiento,
            'fecha' => $cita->fecha_hora->toDateString(),
            'exclude_cita_id' => $cita->id,
        ]))->assertForbidden();
    }

    public function test_el_personal_puede_excluir_la_cita_que_esta_reagendando_del_calculo_de_horarios(): void
    {
        $cita = $this->crearCitaExistente();
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->getJson(route('disponibilidad.index', [
                'tratamiento_id' => $cita->id_tratamiento,
                'fecha' => $cita->fecha_hora->toDateString(),
                'exclude_cita_id' => $cita->id,
            ]))
            ->assertOk()
            ->assertJsonStructure(['horarios']);
    }

    private function crearCitaExistente(): Cita
    {
        $servicio = Servicio::create([
            'nombre' => 'Odontología General',
            'orden' => 1,
            'activo' => true,
        ]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);
        $paciente = Paciente::create([
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5512345678',
            'email' => fake()->unique()->safeEmail(),
        ]);

        return Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => Carbon::tomorrow()->setTime(10, 0),
            'duracion_estimada' => $tratamiento->duracion_estimada,
        ]);
    }
}

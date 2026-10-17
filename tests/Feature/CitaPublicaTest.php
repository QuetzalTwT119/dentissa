<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CitaPublicaTest extends TestCase
{
    use RefreshDatabase;

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

        $this->post(route('citas.publica.store'), [
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha' => $fecha->toDateString(),
            'hora' => '09:00',
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5555555555',
            'email' => 'ana.publica@example.test',
            'motivo' => 'Consulta de prueba',
        ])
            ->assertRedirect(route('citas.publica.create'))
            ->assertSessionHas('success');

        $paciente = Paciente::where('email', 'ana.publica@example.test')->firstOrFail();
        $cita = Cita::where('id_paciente', $paciente->id)->firstOrFail();

        $this->assertSame(EstadoCita::Agendada, $cita->estado);
        $this->assertSame(
            $fecha->copy()->setTime(9, 0)->format('Y-m-d H:i:s'),
            $cita->fecha_hora->format('Y-m-d H:i:s'),
        );
        $this->assertDatabaseHas('notificaciones', [
            'id_paciente' => $paciente->id,
            'id_cita' => $cita->id,
            'canal' => 'cita_agendada',
        ]);
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

}

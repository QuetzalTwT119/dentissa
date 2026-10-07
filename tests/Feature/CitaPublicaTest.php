<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
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
            'canal' => 'cita_agendada',
        ]);
    }
}

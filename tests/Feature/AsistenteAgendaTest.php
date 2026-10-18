<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Paciente;
use App\Models\PreguntaFrecuente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenteAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_asistente_rechaza_fechas_pasadas_en_el_servidor(): void
    {
        $usuario = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'De prueba',
            'telefono' => '5512345678',
            'email' => 'paciente@example.test',
        ]);
        $servicio = Servicio::create(['nombre' => 'Consulta de prueba', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento de prueba',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $this->actingAs($usuario)
            ->from(route('asistente-agenda.create'))
            ->post(route('asistente-agenda.proponer'), [
                'id_paciente' => $paciente->id,
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha' => now()->subDay()->toDateString(),
            ])
            ->assertRedirect(route('asistente-agenda.create'))
            ->assertSessionHasErrors('fecha');

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_el_asistente_respeta_el_margen_y_puede_preparar_una_cita(): void
    {
        $usuario = User::factory()->create(['rol' => RolUsuario::Admin]);
        $paciente = Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'De prueba',
            'telefono' => '5512345678',
            'email' => 'paciente@example.test',
        ]);
        $servicio = Servicio::create(['nombre' => 'Consulta de prueba', 'activo' => true]);
        $largo = Tratamiento::create(['nombre' => 'Tratamiento largo', 'duracion_estimada' => 90, 'categoria' => 'Preventiva', 'activo' => true]);
        $corto = Tratamiento::create(['nombre' => 'Tratamiento corto', 'duracion_estimada' => 30, 'categoria' => 'Preventiva', 'activo' => true]);
        $servicio->tratamientos()->attach([$largo->id, $corto->id]);

        $fecha = Carbon::tomorrow();
        while ($fecha->dayOfWeek === 0) {
            $fecha->addDay();
        }
        HorarioAtencion::where('dia_semana', $fecha->dayOfWeek)->update([
            'abierto' => true,
            'hora_apertura' => 8,
            'hora_cierre' => 18,
            'capacidad_simultanea' => 1,
        ]);

        Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $largo->id,
            'fecha_hora' => $fecha->copy()->setTime(8, 0),
            'duracion_estimada' => 90,
        ]);

        $horarios = app(DisponibilidadService::class)->horariosDisponibles($fecha->toDateString(), $corto);

        $this->assertNotContains('09:30', $horarios);
        $this->assertContains('10:00', $horarios);

        $this->actingAs($usuario)
            ->post(route('asistente-agenda.proponer'), [
                'id_paciente' => $paciente->id,
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $corto->id,
                'fecha' => $fecha->toDateString(),
            ])
            ->assertOk()
            ->assertSee('Propuesta para Paciente De prueba')
            ->assertSee('10:00');
    }

    public function test_el_asistente_paciente_responde_solo_con_preguntas_frecuentes_activas(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Cuánto cuesta una limpieza dental?',
            'respuesta' => 'El costo de la limpieza se confirma antes de la cita.',
            'variantes' => '¿Cuál es el costo de una limpieza?',
            'activa' => true,
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Quiero saber el costo de una limpieza',
            'conversacion_id' => '00000000-0000-4000-8000-000000000001',
        ])
            ->assertOk()
            ->assertJson([
                'encontrada' => true,
                'respuesta' => 'El costo de la limpieza se confirma antes de la cita.',
            ]);
    }

    public function test_el_chat_web_reutiliza_el_flujo_de_horarios_de_telegram(): void
    {
        $servicio = Servicio::create(['nombre' => 'Consulta web', 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Limpieza web', 'duracion_estimada' => 30, 'categoria' => 'Preventiva', 'activo' => true]);
        $servicio->tratamientos()->attach($tratamiento);

        $conversacion = '00000000-0000-4000-8000-000000000002';
        $inicio = $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Quiero una cita',
            'conversacion_id' => $conversacion,
        ])
            ->assertOk();
        $this->assertStringContainsString('Consulta web', $inicio->json('respuesta'));

        $servicioElegido = $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Consulta web',
            'conversacion_id' => $conversacion,
        ])
            ->assertOk();
        $this->assertStringContainsString('Limpieza web', $servicioElegido->json('respuesta'));
    }

    public function test_el_chat_web_dirige_urgencias_y_falta_de_horario_a_las_funciones_correctas(): void
    {
        $conversacion = '00000000-0000-4000-8000-000000000003';

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Tengo dolor intenso y necesito ayuda',
            'conversacion_id' => $conversacion,
        ])->assertOk()
            ->assertJsonPath('accion.etiqueta', 'Solicitar atención urgente');

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'No encuentro horario, quiero lista de espera',
            'conversacion_id' => '00000000-0000-4000-8000-000000000004',
        ])->assertOk()
            ->assertJsonPath('accion.etiqueta', 'Unirme a la lista de espera');
    }
}

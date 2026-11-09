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
use PHPUnit\Framework\Attributes\DataProvider;
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

    /** @return array<string, array{0: callable(): string, 1: callable(): string}> */
    public static function fechasAceptadas(): array
    {
        return [
            'hoy' => [fn () => 'hoy', fn () => today()->toDateString()],
            'mañana con acento' => [fn () => 'Mañana', fn () => today()->addDay()->toDateString()],
            'DD/MM/AAAA' => [fn () => today()->addDays(3)->format('d/m/Y'), fn () => today()->addDays(3)->toDateString()],
            'DD-MM-AAAA' => [fn () => today()->addDays(3)->format('d-m-Y'), fn () => today()->addDays(3)->toDateString()],
            'AAAA-MM-DD' => [fn () => today()->addDays(3)->format('Y-m-d'), fn () => today()->addDays(3)->toDateString()],
        ];
    }

    #[DataProvider('fechasAceptadas')]
    public function test_el_chat_interpreta_formatos_de_fecha_aceptados(callable $entrada, callable $esperada): void
    {
        $tratamiento = $this->prepararConversacionHastaLaFecha('00000000-0000-4000-8000-000000000003');
        $disponibilidad = \Mockery::mock(DisponibilidadService::class);
        $disponibilidad->shouldReceive('horariosDisponibles')
            ->once()
            ->with($esperada(), \Mockery::on(fn ($recibido) => $recibido->is($tratamiento)))
            ->andReturn(['10:00']);
        $this->instance(DisponibilidadService::class, $disponibilidad);

        $respuesta = $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => $entrada(),
            'conversacion_id' => '00000000-0000-4000-8000-000000000003',
        ])->assertOk()->json('respuesta');

        $this->assertStringContainsString('Horarios disponibles para Limpieza web', $respuesta);
        $this->assertStringContainsString('• 10:00', $respuesta);
    }

    /** @return array<string, array{0: string}> */
    public static function fechasRechazadas(): array
    {
        return [
            'día inexistente' => ['31/02/2030'],
            'mes inexistente' => ['2030-13-01'],
            'sin ceros a la izquierda' => ['5/3/2030'],
            'texto libre' => ['pasado mañana'],
        ];
    }

    #[DataProvider('fechasRechazadas')]
    public function test_el_chat_rechaza_fechas_mal_formadas_sin_consultar_disponibilidad(string $entrada): void
    {
        $this->prepararConversacionHastaLaFecha('00000000-0000-4000-8000-000000000004');
        $disponibilidad = \Mockery::mock(DisponibilidadService::class);
        $disponibilidad->shouldNotReceive('horariosDisponibles');
        $this->instance(DisponibilidadService::class, $disponibilidad);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => $entrada,
            'conversacion_id' => '00000000-0000-4000-8000-000000000004',
        ])
            ->assertOk()
            ->assertJsonPath('respuesta', 'No reconocí la fecha. Puedes escribir hoy, mañana, DD/MM/AAAA o AAAA-MM-DD.');
    }

    private function prepararConversacionHastaLaFecha(string $conversacion): Tratamiento
    {
        $servicio = Servicio::create(['nombre' => 'Consulta web', 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Limpieza web', 'duracion_estimada' => 30, 'categoria' => 'Preventiva', 'activo' => true]);
        $servicio->tratamientos()->attach($tratamiento);

        foreach (['Quiero una cita', 'Consulta web', 'Limpieza web'] as $mensaje) {
            $this->postJson(route('asistente-paciente.responder'), [
                'consulta' => $mensaje,
                'conversacion_id' => $conversacion,
            ])->assertOk();
        }

        return $tratamiento;
    }
}

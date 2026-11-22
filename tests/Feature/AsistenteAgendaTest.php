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
        $usuario = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
        $usuario = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
            ->assertJsonPath('respuesta', "No reconocí la fecha. Puedes escribir hoy, mañana, DD/MM/AAAA o AAAA-MM-DD.\n\nSi quieres salir, escribe \"cancelar\".");
    }

    /** @return array<string, array{0: string}> */
    public static function palabrasParaSalir(): array
    {
        return [
            'cancelar' => ['cancelar'],
            'Salir con mayúscula' => ['Salir'],
            'comando con barra' => ['/cancelar'],
        ];
    }

    #[DataProvider('palabrasParaSalir')]
    public function test_cada_paso_explica_como_salir_y_cancelar_cierra_la_busqueda(string $salida): void
    {
        Servicio::create(['nombre' => 'Consulta web', 'activo' => true]);
        $conversacion = '00000000-0000-4000-8000-000000000008';

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Quiero una cita',
            'conversacion_id' => $conversacion,
        ])->assertOk()->assertJsonPath('respuesta', fn (string $r) => str_ends_with($r, 'Si quieres salir, escribe "cancelar".'));

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => $salida,
            'conversacion_id' => $conversacion,
        ])->assertOk()->assertJsonPath('respuesta', 'Cancelé la consulta de horarios. Cuando quieras, escribe que buscas una cita.');

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Consulta web',
            'conversacion_id' => $conversacion,
        ])->assertOk()->assertJsonPath('respuesta', fn (string $r) => ! str_contains($r, 'tratamiento') && ! str_contains($r, 'No identifiqué'));
    }

    public function test_cancelar_sin_una_busqueda_abierta_no_se_intercepta(): void
    {
        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'cancelar',
            'conversacion_id' => '00000000-0000-4000-8000-000000000009',
        ])->assertOk()->assertJsonPath('respuesta', fn (string $r) => ! str_contains($r, 'Cancelé la consulta'));
    }

    public function test_preguntar_el_horario_a_mitad_de_la_busqueda_lo_responde_sin_perder_el_paso(): void
    {
        $servicio = Servicio::create(['nombre' => 'Consulta web', 'activo' => true]);
        $servicio->tratamientos()->attach(Tratamiento::create(['nombre' => 'Limpieza web', 'duracion_estimada' => 30, 'categoria' => 'Preventiva', 'activo' => true]));
        $conversacion = '00000000-0000-4000-8000-000000000010';

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Quiero una cita',
            'conversacion_id' => $conversacion,
        ])->assertOk();

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿A qué hora abren?',
            'conversacion_id' => $conversacion,
        ])->assertOk()->assertJsonPath('respuesta', fn (string $r) => str_starts_with($r, 'Nuestro horario de atención:')
            && str_contains($r, 'Seguimos con tu búsqueda de horarios'));

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Consulta web',
            'conversacion_id' => $conversacion,
        ])->assertOk()->assertJsonPath('respuesta', fn (string $r) => str_contains($r, 'Elige el tratamiento') && str_contains($r, 'Limpieza web'));
    }

    /** @return array<string, array{0: string}> */
    public static function preguntasDeHorarioDeAtencion(): array
    {
        return [
            'horario de atención' => ['¿Cuál es el horario de atención?'],
            'a qué hora abren' => ['¿A qué hora abren?'],
            'qué días atienden' => ['¿Qué días atienden?'],
        ];
    }

    #[DataProvider('preguntasDeHorarioDeAtencion')]
    public function test_preguntar_el_horario_de_atencion_responde_el_horario_sin_iniciar_una_reserva(string $pregunta): void
    {
        Servicio::create(['nombre' => 'Consulta web', 'activo' => true]);
        HorarioAtencion::query()->update(['abierto' => false]);
        HorarioAtencion::whereIn('dia_semana', [1, 2, 3, 4, 5])->update(['abierto' => true, 'hora_apertura' => 8, 'hora_cierre' => 18]);
        $conversacion = '00000000-0000-4000-8000-000000000005';

        $respuesta = $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => $pregunta,
            'conversacion_id' => $conversacion,
        ])->assertOk()->json('respuesta');

        $this->assertStringContainsString('Nuestro horario de atención: Lunes a viernes de 8:00 a 18:00 hrs.', $respuesta);
        $this->assertStringNotContainsString('¿Qué tipo de atención necesitas?', $respuesta);

        // No quedó un flujo de reserva abierto: el siguiente mensaje no se toma como servicio.
        $siguiente = $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Consulta web',
            'conversacion_id' => $conversacion,
        ])->assertOk()->json('respuesta');
        $this->assertStringNotContainsString('Elige el tratamiento', $siguiente);
    }

    /** @return array<string, array{0: string}> */
    public static function preguntasDeReserva(): array
    {
        return [
            'horarios disponibles' => ['¿Qué horarios disponibles tienen?'],
            'cita y hora de apertura' => ['Quiero una cita, ¿a qué hora abren?'],
        ];
    }

    #[DataProvider('preguntasDeReserva')]
    public function test_pedir_horarios_disponibles_o_una_cita_inicia_la_busqueda_de_turnos(string $pregunta): void
    {
        Servicio::create(['nombre' => 'Consulta web', 'activo' => true]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => $pregunta,
            'conversacion_id' => '00000000-0000-4000-8000-000000000006',
        ])
            ->assertOk()
            ->assertJsonPath('respuesta', fn (string $respuesta) => str_contains($respuesta, '¿Qué tipo de atención necesitas?'));
    }

    public function test_una_pregunta_frecuente_aprobada_sobre_el_horario_tiene_prioridad(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Cuál es el horario de atención?',
            'respuesta' => 'Respuesta aprobada por la clínica.',
            'activa' => true,
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿Cuál es el horario de atención?',
            'conversacion_id' => '00000000-0000-4000-8000-000000000007',
        ])
            ->assertOk()
            ->assertJsonPath('respuesta', 'Respuesta aprobada por la clínica.');
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

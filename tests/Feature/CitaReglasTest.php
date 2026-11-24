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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CitaReglasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Estas pruebas validan las transiciones, no integraciones externas.
        Http::fake();
        config()->set('dentissa.whatsapp.phone_number_id', null);
        config()->set('dentissa.whatsapp.access_token', null);
    }

    public function test_el_filtro_de_citas_permite_fechas_futuras_y_combina_fecha_con_estado(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $fecha = Carbon::today()->addDays(14);

        $pacienteAgendado = Paciente::create([
            'nombre' => 'Cita',
            'apellido' => 'Futura agendada',
            'telefono' => '5555551001',
            'email' => 'futura-agendada@example.test',
        ]);
        $pacienteConfirmado = Paciente::create([
            'nombre' => 'Cita',
            'apellido' => 'Futura confirmada',
            'telefono' => '5555551002',
            'email' => 'futura-confirmada@example.test',
        ]);

        foreach ([
            [$pacienteAgendado, EstadoCita::Agendada],
            [$pacienteConfirmado, EstadoCita::Confirmada],
        ] as [$paciente, $estado]) {
            Cita::create([
                'id_paciente' => $paciente->id,
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha_hora' => $fecha->copy()->setTime(10, 0),
                'duracion_estimada' => 30,
                'estado' => $estado,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('citas.index', [
                'fecha' => $fecha->toDateString(),
                'estado' => EstadoCita::Agendada->value,
            ]))
            ->assertOk()
            ->assertSee('Cita Futura agendada')
            ->assertDontSee('Cita Futura confirmada')
            ->assertSee('value="'.$fecha->toDateString().'"', false);
    }

    public function test_el_buscador_de_citas_acepta_nombre_completo_telefono_servicio_y_tratamiento(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $pacienteEncontrado = Paciente::create([
            'nombre' => 'Ana María',
            'apellido' => 'Torres Luna',
            'telefono' => '5555550012',
            'email' => 'ana.torres@example.test',
        ]);
        $pacienteOtro = Paciente::create([
            'nombre' => 'Rosa',
            'apellido' => 'Leal',
            'telefono' => '5555550013',
            'email' => 'rosa.leal@example.test',
        ]);

        foreach ([$pacienteEncontrado, $pacienteOtro] as $paciente) {
            Cita::create([
                'id_paciente' => $paciente->id,
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha_hora' => $this->fechaFutura()->setTime(10, 0),
                'duracion_estimada' => 30,
                'estado' => EstadoCita::Confirmada,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('citas.index', ['buscar' => 'Ana Torres', 'estado' => EstadoCita::Confirmada->value]))
            ->assertOk()
            ->assertSee('Ana María Torres Luna')
            ->assertDontSee('Rosa Leal')
            ->assertSee('value="Ana Torres"', false)
            ->assertSee('value="confirmada" selected', false)
            // Buscador global (components/filtro): actualiza solo la zona de citas sin recargar.
            ->assertSee('data-filtro-buscar', false)
            ->assertSee('data-filtro-resultados="[data-citas-results]"', false)
            ->assertSee('data-citas-results', false);

        $this->actingAs($admin)
            ->get(route('citas.index', ['buscar' => '5555550012']))
            ->assertOk()
            ->assertSee('Ana María Torres Luna')
            ->assertDontSee('Rosa Leal');

        $this->actingAs($admin)
            ->get(route('citas.index', ['buscar' => 'Tratamiento QA']))
            ->assertOk()
            ->assertSee('Ana María Torres Luna')
            ->assertSee('Rosa Leal');
    }

    public function test_el_listado_de_citas_muestra_20_por_defecto_y_permite_elegir_50(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $paciente = $this->crearPaciente();

        foreach (range(1, 21) as $numero) {
            Cita::create([
                'id_paciente' => $paciente->id,
                'id_servicio' => $servicio->id,
                'id_tratamiento' => $tratamiento->id,
                'fecha_hora' => Carbon::today()->addDays($numero)->setTime(10, 0),
                'duracion_estimada' => 30,
                'estado' => EstadoCita::Agendada,
            ]);
        }

        // La paginación vive en "Todas las fechas"; la vista por día muestra el día completo.
        $this->actingAs($admin)
            ->get(route('citas.index', ['todas' => 1]))
            ->assertOk()
            ->assertSee('Mostrando')
            ->assertSee('1–20', false)
            ->assertSee('de <strong class="font-semibold tabular-nums text-ink">21</strong> citas', false)
            ->assertSee('<option value="20" selected>20</option>', false);

        $this->actingAs($admin)
            ->get(route('citas.index', ['todas' => 1, 'per_page' => 50]))
            ->assertOk()
            ->assertSee('1–21', false)
            ->assertSee('<option value="50" selected>50</option>', false);
    }

    public function test_no_se_puede_agendar_un_tratamiento_que_no_pertenece_al_servicio(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
            'acepta_aviso' => '1',
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
            'acepta_aviso' => '1',
        ])->assertSessionHasErrors('hora');

        $this->assertDatabaseCount('citas', 0);
    }

    public function test_el_personal_puede_iniciar_y_completar_una_cita_confirmada(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Doctor]); // Personal con Agenda (22/11/2026: la asistente ya no la tiene).
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

        // QA 24/11/2026: una cita solo se inicia el día que le toca.
        $this->actingAs($asistente)
            ->patch(route('citas.start', $cita))
            ->assertSessionHasErrors('estado');
        $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado' => EstadoCita::Confirmada->value]);

        Carbon::setTestNow($cita->fecha_hora->copy()->subMinutes(5));

        try {
            $this->actingAs($asistente)
                ->patch(route('citas.start', $cita))
                ->assertRedirectContains(route('citas.index').'?fecha=')
                ->assertSessionHas('success', 'Cita iniciada correctamente.');

            $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado' => EstadoCita::EnCurso->value]);

            $this->actingAs($asistente)
                ->patch(route('citas.complete', $cita))
                ->assertRedirectContains(route('citas.index').'?fecha=')
                ->assertSessionHas('success', 'Cita completada correctamente.');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado' => EstadoCita::Completada->value]);
    }

    public function test_una_cita_en_curso_muestra_completar_sin_acciones_secundarias(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $paciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => $this->fechaFutura()->setTime(10, 0),
            'duracion_estimada' => $tratamiento->duracion_estimada,
            'estado' => EstadoCita::EnCurso,
        ]);

        $this->actingAs($admin)
            ->get(route('citas.index', ['fecha' => $cita->fecha_hora->toDateString()]))
            ->assertOk()
            ->assertSee('Completar')
            ->assertDontSee('Más acciones')
            ->assertDontSee('Reagendar')
            // "Cancelar" a secas existe en el diálogo de confirmación de todo el panel.
            ->assertDontSee('Cancelar cita')
            ->assertDontSee('Sin acciones');
    }

    public function test_el_personal_puede_crear_una_cita_interna_ya_confirmada(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
            ->assertRedirectContains(route('citas.index').'?fecha=')
            ->assertSessionHas('success', 'Cita confirmada correctamente.');

        $this->assertDatabaseHas('citas', [
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'estado' => EstadoCita::Confirmada->value,
            'confirmada_por' => $admin->id,
            'motivo' => 'Consulta interna de QA',
        ]);
        $cita = Cita::query()->where('id_paciente', $paciente->id)->latest('id')->firstOrFail();
        $this->assertNotNull($cita->fecha_confirmacion);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $admin->id,
            'accion' => 'cita.creada',
            'tabla' => 'citas',
            'registro_id' => Cita::query()->latest('id')->value('id'),
        ]);
    }

    public function test_no_se_supera_la_capacidad_simultanea_para_un_mismo_horario(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
            ->assertRedirectContains(route('citas.index').'?fecha=');

        $this->actingAs($admin)
            ->post(route('citas.store'), $datosCita + ['id_paciente' => $segundoPaciente->id])
            ->assertSessionHasErrors('hora');

        $this->assertDatabaseCount('citas', 1);
    }

    public function test_solo_el_personal_puede_autorizar_la_creacion_interna(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->assertTrue(Gate::forUser($admin)->allows('create', Cita::class));
        $this->assertTrue(Gate::forUser($doctor)->allows('create', Cita::class));
        // 24/11/2026: la asistente agenda (módulo "agenda" en config/permisos.php).
        $this->assertTrue(Gate::forUser($asistente)->allows('create', Cita::class));
    }

    public function test_una_cita_agendada_puede_confirmarse(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
            ->get(route('citas.index', ['fecha' => $cita->fecha_hora->toDateString()]))
            ->assertOk()
            ->assertSee('Confirmar')
            ->assertSee('Más acciones')
            ->assertSee('Reagendar')
            ->assertSee('Cancelar')
            ->assertDontSee(route('citas.no-show', $cita), false);

        $this->actingAs($admin)
            ->patch(route('citas.confirm', $cita))
            ->assertRedirectContains(route('citas.index').'?fecha=')
            ->assertSessionHas('success', 'Cita confirmada correctamente.');

        $this->get(route('citas.index', ['fecha' => $cita->fecha_hora->toDateString()]))
            ->assertOk()
            ->assertSee('Cita confirmada correctamente.')
            ->assertSee('Confirmada')
            // QA 24/11/2026: es de mañana, todavía no se puede iniciar.
            ->assertDontSee(route('citas.start', $cita), false)
            // Aún no llega la hora: el servidor rechazaría la inasistencia, así que no se ofrece.
            ->assertDontSee(route('citas.no-show', $cita), false);

        Carbon::setTestNow($cita->fecha_hora->copy()->addMinutes(20));

        try {
            $this->get(route('citas.index', ['fecha' => $cita->fecha_hora->toDateString()]))
                ->assertOk()
                ->assertSee(route('citas.start', $cita), false)
                ->assertSee(route('citas.no-show', $cita), false)
                ->assertSee('data-confirmar-titulo="¿Marcar esta cita como no asistida?"', false)
                ->assertSee('data-confirmar="Esta acción no se puede deshacer."', false);
        } finally {
            Carbon::setTestNow();
        }

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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
            ->assertRedirectContains(route('citas.index').'?fecha=')
            ->assertSessionHas('success', 'Cita cancelada correctamente.');

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
        $asistente = User::factory()->create(['rol' => RolUsuario::Doctor]); // Personal con Agenda (22/11/2026: la asistente ya no la tiene).
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
            ->assertRedirectContains(route('citas.index').'?fecha=')
            ->assertSessionHas('success', 'Cita reagendada correctamente.');

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

    public function test_una_cita_confirmada_conserva_su_estado_al_reagendarse(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Doctor]); // Personal con Agenda (22/11/2026: la asistente ya no la tiene).
        $paciente = $this->crearPaciente();
        [$servicio, $tratamiento] = $this->crearCatalogo();
        $fechaConfirmacion = now()->subMinute();
        $nuevaFecha = $this->fechaFutura()->addWeek()->setTime(11, 0);
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => $this->fechaFutura()->setTime(10, 0),
            'duracion_estimada' => $tratamiento->duracion_estimada,
            'estado' => EstadoCita::Confirmada,
            'fecha_confirmacion' => $fechaConfirmacion,
        ]);

        $this->actingAs($asistente)
            ->put(route('citas.update', $cita), [
                'fecha' => $nuevaFecha->toDateString(),
                'hora' => '11:00',
            ])
            ->assertRedirectContains(route('citas.index').'?fecha=');

        $cita->refresh();

        $this->assertSame(EstadoCita::Confirmada, $cita->estado);
        $this->assertTrue($cita->fecha_hora->equalTo($nuevaFecha));
        $this->assertSame(
            $fechaConfirmacion->format('Y-m-d H:i:s'),
            $cita->fecha_confirmacion->format('Y-m-d H:i:s'),
        );
    }

    public function test_una_cita_confirmada_puede_marcarse_como_no_asistio(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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

        Carbon::setTestNow($cita->fecha_hora);

        try {
            $this->actingAs($admin)
                ->patch(route('citas.no-show', $cita))
                ->assertRedirectContains(route('citas.index').'?fecha=')
                ->assertSessionHas('success', 'Cita marcada como no asistida.');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertDatabaseHas('citas', ['id' => $cita->id, 'estado' => EstadoCita::NoAsistio->value]);
    }

    public function test_no_se_puede_marcar_una_cita_futura_como_no_asistio(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Doctor]); // Personal con Agenda (22/11/2026: la asistente ya no la tiene).
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
            ->patch(route('citas.no-show', $cita))
            ->assertSessionHasErrors('estado');

        $this->assertSame(EstadoCita::Confirmada, $cita->fresh()->estado);
        $this->assertDatabaseMissing('logs_auditoria', [
            'accion' => 'cita.no_asistio',
            'tabla' => 'citas',
            'registro_id' => $cita->id,
        ]);
    }

    public function test_las_transiciones_invalidas_no_cambian_el_estado_de_la_cita(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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

<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\HistoriaClinica;
use App\Models\HistorialClinico;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cuestionario de historia clínica (22/11/2026): una por paciente, en el
 * expediente, con aviso rojo de riesgos, en el PDF y sin datos de salud en
 * Control de actividades.
 */
class HistoriaClinicaTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $this->paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Martínez', 'telefono' => '5512345678']);
    }

    private function datos(array $extra = []): array
    {
        return $extra + [
            'fecha_nacimiento' => '1990-05-10',
            'sexo' => 'femenino',
            'ocupacion' => 'Maestra',
            'tipo_sangre' => 'O+',
            'antecedentes_familiares' => ['diabetes'],
            'antecedentes_personales' => ['hipertension', 'asma'],
            'alergias' => ['penicilina'],
            'alergias_otras' => 'Mariscos',
            'medicamentos_actuales' => 'Losartán 50 mg',
            'embarazo' => '1',
            'lactancia' => '0',
            'antecedentes_dentales' => ['sangrado_encias'],
            'habitos' => ['bruxismo'],
            'observaciones' => 'Nerviosa con las agujas.',
        ];
    }

    public function test_sin_historia_el_expediente_invita_a_llenarla(): void
    {
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.index', $this->paciente))->assertOk()
            ->assertSee('Aún no tiene historia clínica')
            ->assertSee('Llenar cuestionario')
            ->assertSee(route('admin.pacientes.historia.edit', $this->paciente), false)
            ->assertDontSee('data-aviso-clinico', false);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.historia.edit', $this->paciente))->assertOk()
            ->assertSee('data-cuestionario-historia', false)
            ->assertSee('Antecedentes personales')
            ->assertSee('Problemas de coagulación o sangrado')
            ->assertSee('Alergias')
            ->assertSee('Hábitos');
    }

    public function test_se_guarda_y_el_aviso_rojo_sale_en_el_expediente_y_en_la_consulta(): void
    {
        $this->actingAs($this->doctora)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos())
            ->assertRedirect(route('admin.pacientes.historial.index', $this->paciente))
            ->assertSessionHas('success');

        $historia = $this->paciente->fresh()->historiaClinica;
        $this->assertSame(['penicilina'], $historia->alergias);
        $this->assertSame(['hipertension', 'asma'], $historia->antecedentes_personales);
        $this->assertTrue($historia->embarazo);
        $this->assertSame($this->doctora->id, $historia->actualizado_por);
        $this->assertSame(
            ['Alergia: Penicilina, Mariscos', 'Hipertensión', 'Embarazo'],
            $historia->alertas(),
        );

        foreach ([route('admin.pacientes.historial.index', $this->paciente), route('admin.pacientes.historial.create', $this->paciente)] as $url) {
            $this->actingAs($this->doctora)->get($url)->assertOk()
                ->assertSee('data-aviso-clinico', false)
                ->assertSee('Antes de atender, ten en cuenta:')
                ->assertSee('Alergia: Penicilina, Mariscos');
        }
    }

    public function test_embarazo_solo_aplica_a_mujeres_y_editar_actualiza(): void
    {
        $this->actingAs($this->doctora)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos(['sexo' => 'masculino']));
        $this->assertNull($this->paciente->fresh()->historiaClinica->embarazo);

        // Editar: desmarcar todo deja las listas vacías (el navegador no manda casillas vacías).
        $this->actingAs($this->doctora)->put(route('admin.pacientes.historia.update', $this->paciente), ['sexo' => 'masculino', 'medicamentos_actuales' => 'Ninguno']);
        $historia = $this->paciente->fresh()->historiaClinica;
        $this->assertSame([], $historia->alergias);
        $this->assertSame([], $historia->alertas());
        $this->assertSame(1, HistoriaClinica::count());
    }

    public function test_control_de_actividades_no_guarda_datos_de_salud(): void
    {
        $this->actingAs($this->doctora)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos());
        $this->actingAs($this->doctora)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos(['alergias' => []]));

        $registros = LogAuditoria::whereIn('accion', ['historia_clinica.creada', 'historia_clinica.actualizada'])->orderBy('id')->get();
        $this->assertSame(['historia_clinica.creada', 'historia_clinica.actualizada'], $registros->pluck('accion')->all());
        $this->assertSame(['alergias'], $registros->last()->datos_nuevos['campos_modificados']);

        $json = $registros->toJson();
        foreach (['Penicilina', 'penicilina', 'Mariscos', 'Losartán', 'hipertension', 'Nerviosa'] as $dato) {
            $this->assertStringNotContainsString($dato, $json, "La bitácora no debe guardar «{$dato}»");
        }
    }

    /** 23/11/2026: lo que faltaba frente al formato de historia clínica odontológica. */
    public function test_guarda_contacto_de_emergencia_medico_y_tratamiento_actual(): void
    {
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historia.edit', $this->paciente))->assertOk()
            ->assertSee('Contacto de emergencia y médico')
            ->assertSee('Gastritis o úlcera')
            ->assertSee('Cirugías previas')
            ->assertSee('¿Está en tratamiento médico? ¿De qué?');

        $this->actingAs($this->doctora)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos([
            'estado_civil' => 'casado',
            'tratamiento_medico_actual' => 'Control de presión',
            'contacto_emergencia_nombre' => 'Luis Martínez', 'contacto_emergencia_parentesco' => 'Esposo', 'contacto_emergencia_telefono' => '755 111 2233',
            'medico_cabecera_nombre' => 'Dr. Pérez', 'medico_cabecera_telefono' => '7551234567',
            'antecedentes_personales' => ['gastritis', 'cirugias'],
        ]))->assertSessionHasNoErrors();

        $historia = $this->paciente->fresh()->historiaClinica;
        $this->assertSame('casado', $historia->estado_civil);
        $this->assertSame('Esposo', $historia->contacto_emergencia_parentesco);
        $this->assertSame(['gastritis', 'cirugias'], $historia->antecedentes_personales);

        $this->actingAs($this->doctora)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos([
            'estado_civil' => 'inventado', 'contacto_emergencia_telefono' => 'llamar a Luis',
        ]))->assertSessionHasErrors(['estado_civil', 'contacto_emergencia_telefono']);
    }

    public function test_valores_invalidos_se_rechazan(): void
    {
        $this->actingAs($this->doctora)->from(route('admin.pacientes.historia.edit', $this->paciente))
            ->put(route('admin.pacientes.historia.update', $this->paciente), [
                'fecha_nacimiento' => now()->addDay()->toDateString(),
                'sexo' => 'x',
                'tipo_sangre' => 'Z+',
                'alergias' => ['veneno_inventado'],
            ])
            ->assertSessionHasErrors(['fecha_nacimiento', 'sexo', 'tipo_sangre', 'alergias.0']);
        $this->assertNull($this->paciente->fresh()->historiaClinica);
    }

    public function test_el_pdf_incluye_la_historia_aunque_no_haya_consultas(): void
    {
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.pdf', $this->paciente))
            ->assertRedirect(route('admin.pacientes.historial.index', $this->paciente));

        $this->actingAs($this->doctora)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos());

        $respuesta = $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.pdf', $this->paciente));
        $respuesta->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $respuesta->headers->get('content-type'));
    }

    public function test_el_doctor_la_llena_desde_el_expediente(): void
    {
        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor]);

        $this->actingAs($doctor)->get(route('admin.pacientes.historia.edit', $this->paciente))->assertOk()
            ->assertSee(route('admin.pacientes.historial.index', $this->paciente), false);
        $this->actingAs($doctor)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos())
            ->assertRedirect(route('admin.pacientes.historial.index', $this->paciente));
        // El menú no le duplica el enlace: ella entra por Pacientes.
        $this->actingAs($doctor)->get(route('dashboard'))->assertDontSee(route('admin.historias.index'), false);
    }

    /** 22/11/2026: la asistente llena el cuestionario, pero no ve el expediente. */
    public function test_la_asistente_llena_la_historia_sin_ver_el_expediente(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        Paciente::create(['nombre' => 'Beto', 'apellido' => 'Ruiz', 'telefono' => '5598765432']);

        // Menú y lista con el estado de cada paciente.
        $this->actingAs($asistente)->get(route('dashboard'))->assertOk()
            ->assertSee(route('admin.historias.index'), false)
            ->assertDontSee(route('admin.pacientes.index'), false);
        $this->actingAs($asistente)->get(route('admin.historias.index'))->assertOk()
            ->assertSee('Ana Martínez')->assertSee('Beto Ruiz')
            ->assertSee('Pendiente')->assertSee('Llenar cuestionario');
        $this->actingAs($asistente)->get(route('admin.historias.index', ['buscar' => 'Beto']))->assertOk()
            ->assertSee('Beto Ruiz')->assertDontSee('Ana Martínez');

        // Cuestionario: regresa a la lista, no al expediente.
        $this->actingAs($asistente)->get(route('admin.pacientes.historia.edit', $this->paciente))->assertOk()
            ->assertSee('Historias clínicas')
            ->assertDontSee(route('admin.pacientes.historial.index', $this->paciente), false);
        $this->actingAs($asistente)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos())
            ->assertRedirect(route('admin.historias.index'));
        $this->assertSame($asistente->id, $this->paciente->fresh()->historiaClinica->actualizado_por);
        $this->actingAs($asistente)->get(route('admin.historias.index'))->assertSee('Llena')->assertSee('Ver o editar');

        // Lo demás del expediente sigue cerrado.
        foreach ([
            route('admin.pacientes.index'),
            route('admin.pacientes.historial.index', $this->paciente),
            route('admin.pacientes.historial.create', $this->paciente),
            route('admin.pacientes.historial.pdf', $this->paciente),
            route('admin.pacientes.edit', $this->paciente),
        ] as $url) {
            $this->actingAs($asistente)->get($url)->assertForbidden();
        }
    }

    public function test_cita_completada_ofrece_registrar_su_consulta(): void
    {
        // Mediodía fijo (25/11/2026): la agenda muestra el día de hoy, y entre las
        // 00:00 y la 01:00 "hace una hora" caía en ayer y la prueba fallaba en CI.
        \Illuminate\Support\Carbon::setTestNow(now()->setTime(12, 0));
        $this->beforeApplicationDestroyed(fn () => \Illuminate\Support\Carbon::setTestNow());

        $cita = Cita::create(['id_paciente' => $this->paciente->id, 'fecha_hora' => now()->subHour()->startOfHour(), 'duracion_estimada' => 30, 'estado' => 'completada']);

        $this->actingAs($this->doctora)->get(route('citas.index', ['estado' => 'completada']))->assertOk()
            ->assertSee('data-registrar-consulta', false)
            ->assertSee(route('admin.pacientes.historial.create', [$this->paciente->id, 'id_cita' => $cita->id]), false);

        // La consulta nueva viene con esa cita ya elegida.
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.create', [$this->paciente, 'id_cita' => $cita->id]))->assertOk()
            ->assertSee('value="'.$cita->id.'" selected', false);

        // Con su consulta ya registrada, el botón desaparece.
        HistorialClinico::create(['id_paciente' => $this->paciente->id, 'id_cita' => $cita->id, 'id_doctor' => $this->doctora->id,
            'fecha_consulta' => now()->toDateString(), 'tipo_consulta' => 'primera_vez', 'motivo_consulta' => 'Revisión']);
        $this->actingAs($this->doctora)->get(route('citas.index', ['estado' => 'completada']))->assertOk()
            ->assertDontSee('data-registrar-consulta', false);

        // La columna de Pacientes ya no se confunde con las citas.
        $this->actingAs($this->doctora)->get(route('admin.pacientes.index'))->assertSee('Consultas en expediente');
    }
}

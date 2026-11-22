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

    public function test_solo_quien_tiene_el_modulo_pacientes_la_ve(): void
    {
        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($doctor)->get(route('admin.pacientes.historia.edit', $this->paciente))->assertOk();
        $this->actingAs($asistente)->get(route('admin.pacientes.historia.edit', $this->paciente))->assertForbidden();
        $this->actingAs($asistente)->put(route('admin.pacientes.historia.update', $this->paciente), $this->datos())->assertForbidden();
        $this->assertNull($this->paciente->fresh()->historiaClinica);
    }

    public function test_cita_completada_ofrece_registrar_su_consulta(): void
    {
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

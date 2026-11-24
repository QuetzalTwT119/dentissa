<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\HistoriaClinica;
use App\Models\HistorialClinico;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Receta en PDF (24/11/2026): sale de una consulta con medicamentos y lleva la
 * cédula profesional de quien atendió (Mi perfil).
 */
class RecetaPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'cedula_profesional' => '12345678', 'especialidad' => 'Cirujana dentista']);
        $this->paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5511112222']);
    }

    private function consulta(array $extra = []): HistorialClinico
    {
        return HistorialClinico::create($extra + [
            'id_paciente' => $this->paciente->id, 'id_doctor' => $this->doctora->id, 'fecha_consulta' => '2026-11-24',
            'tipo_consulta' => 'primera_vez', 'motivo_consulta' => 'Dolor', 'diagnostico' => 'Pulpitis',
            'medicamentos_recetados' => "Ibuprofeno 400 mg\n1 tableta cada 8 horas por 3 días",
        ]);
    }

    public function test_el_expediente_ofrece_receta_solo_si_hay_medicamentos(): void
    {
        $conMedicamentos = $this->consulta();
        $sinMedicamentos = $this->consulta(['medicamentos_recetados' => null]);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.index', $this->paciente))->assertOk()
            ->assertSee(route('admin.pacientes.historial.receta', [$this->paciente, $conMedicamentos]), false)
            ->assertDontSee(route('admin.pacientes.historial.receta', [$this->paciente, $sinMedicamentos]), false);
    }

    public function test_genera_el_pdf_con_la_cedula_y_lo_registra(): void
    {
        HistoriaClinica::create(['id_paciente' => $this->paciente->id, 'fecha_nacimiento' => '1990-05-10', 'alergias' => ['penicilina'], 'alergias_otras' => 'Látex']);
        $consulta = $this->consulta();

        $respuesta = $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.receta', [$this->paciente, $consulta]));
        $respuesta->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $respuesta->headers->get('content-type'));
        $this->assertStringContainsString('inline', (string) $respuesta->headers->get('content-disposition'));
        $this->assertTrue(LogAuditoria::where('accion', 'receta.generada')->where('registro_id', $consulta->id)->exists());

        // El contenido de la vista: cédula, edad y alergias.
        $html = view('pdf.receta', ['paciente' => $this->paciente, 'consulta' => $consulta, 'doctor' => $this->doctora, 'edad' => 36, 'alergias' => 'Penicilina, Látex'])->render();
        $this->assertStringContainsString('Cédula profesional: 12345678', $html);
        $this->assertStringContainsString('Cirujana dentista', $html);
        $this->assertStringContainsString('36 años', $html);
        $this->assertStringContainsString('Penicilina, Látex', $html);
        $this->assertStringContainsString('Ibuprofeno 400 mg', $html);
    }

    public function test_sin_cedula_o_sin_medicamentos_explica_que_falta(): void
    {
        $sinMedicamentos = $this->consulta(['medicamentos_recetados' => '']);
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.receta', [$this->paciente, $sinMedicamentos]))
            ->assertRedirect(route('admin.pacientes.historial.index', $this->paciente))
            ->assertSessionHas('error');

        $this->doctora->update(['cedula_profesional' => null]);
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.receta', [$this->paciente, $this->consulta()]))
            ->assertSessionHas('error', fn (string $mensaje) => str_contains($mensaje, 'cédula profesional') && str_contains($mensaje, 'Mi perfil'));
    }

    public function test_la_consulta_de_otro_paciente_no_se_imprime(): void
    {
        $otro = Paciente::create(['nombre' => 'Beto', 'apellido' => 'Paz', 'telefono' => '5533334444']);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.receta', [$otro, $this->consulta()]))->assertNotFound();
    }

    public function test_la_cedula_se_guarda_en_mi_perfil(): void
    {
        $this->actingAs($this->doctora)->get(route('profile.edit'))->assertOk()->assertSee('data-cedula', false);

        $this->actingAs($this->doctora)->patch(route('profile.update'), [
            'nombre' => $this->doctora->nombre, 'apellido' => $this->doctora->apellido, 'telefono' => '5512345678', 'email' => $this->doctora->email,
            'cedula_profesional' => '87654321', 'especialidad' => 'Ortodoncista',
        ])->assertSessionHasNoErrors();

        $this->assertSame('87654321', $this->doctora->fresh()->cedula_profesional);
        $this->assertSame('Ortodoncista', $this->doctora->fresh()->especialidad);

        // La asistente no la ve (no atiende consultas).
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $this->actingAs($asistente)->get(route('profile.edit'));
        $this->assertFalse($asistente->puede('pacientes'));
    }
}

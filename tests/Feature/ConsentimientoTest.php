<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Consentimiento;
use App\Models\HistoriaClinica;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Consentimiento informado (24/11/2026): se escribe desde el expediente, se
 * guarda y se imprime para firmarlo a mano.
 */
class ConsentimientoTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'nombre' => 'Melissa', 'apellido' => 'López', 'cedula_profesional' => '12345678']);
        $this->paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5511112222']);
    }

    private function datos(array $extra = []): array
    {
        return $extra + [
            'procedimiento' => 'Extracción simple', 'dientes' => '48',
            'beneficios' => Consentimiento::BENEFICIOS, 'riesgos' => 'Sangrado e inflamación.', 'alternativas' => Consentimiento::ALTERNATIVAS,
        ];
    }

    public function test_el_expediente_ofrece_nuevo_consentimiento_con_textos_de_partida(): void
    {
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.index', $this->paciente))->assertOk()
            ->assertSee('Consentimientos informados')
            ->assertSee(route('admin.pacientes.consentimientos.create', $this->paciente), false);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.consentimientos.create', $this->paciente))->assertOk()
            ->assertSee('data-consentimiento', false)
            ->assertSee(Consentimiento::RIESGOS);
    }

    public function test_guarda_registra_y_lo_deja_listo_para_imprimir(): void
    {
        HistoriaClinica::create(['id_paciente' => $this->paciente->id, 'fecha_nacimiento' => '1990-05-10', 'alergias' => ['penicilina']]);

        $this->actingAs($this->doctora)->post(route('admin.pacientes.consentimientos.store', $this->paciente), $this->datos())
            ->assertRedirect(route('admin.pacientes.historial.index', $this->paciente))
            ->assertSessionHas('imprimir_consentimiento');

        $consentimiento = Consentimiento::sole();
        $this->assertSame($this->doctora->id, $consentimiento->id_doctor);
        $this->assertTrue(LogAuditoria::where('accion', 'consentimiento.creado')->where('registro_id', $consentimiento->id)->exists());
        $this->assertStringNotContainsString('Sangrado', LogAuditoria::where('accion', 'consentimiento.creado')->sole()->toJson());

        $pdf = $this->actingAs($this->doctora)->get(route('admin.pacientes.consentimientos.pdf', [$this->paciente, $consentimiento]));
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));

        $html = view('pdf.consentimiento', ['paciente' => $this->paciente, 'consentimiento' => $consentimiento->load('doctor'), 'edad' => 36, 'alergias' => 'Penicilina'])->render();
        foreach (['CARTA DE CONSENTIMIENTO INFORMADO', 'Extracción simple', 'Dientes: 48', 'Melissa López', 'Céd. prof. 12345678', 'Sangrado e inflamación.', 'Penicilina', 'Testigo 1', 'NOM-004-SSA3-2012'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }

        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.index', $this->paciente))
            ->assertSee('Extracción simple')
            ->assertSee(route('admin.pacientes.consentimientos.pdf', [$this->paciente, $consentimiento]), false);
    }

    public function test_pide_los_textos_y_no_imprime_el_de_otro_paciente(): void
    {
        $this->actingAs($this->doctora)->post(route('admin.pacientes.consentimientos.store', $this->paciente), ['procedimiento' => '', 'beneficios' => '', 'riesgos' => '', 'alternativas' => ''])
            ->assertSessionHasErrors(['procedimiento', 'beneficios', 'riesgos', 'alternativas']);

        $ajeno = Consentimiento::create($this->datos(['id_paciente' => Paciente::create(['nombre' => 'B', 'apellido' => 'C', 'telefono' => '5599998888'])->id]));
        $this->actingAs($this->doctora)->get(route('admin.pacientes.consentimientos.pdf', [$this->paciente, $ajeno]))->assertNotFound();
    }

    public function test_la_asistente_no_accede(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)->get(route('admin.pacientes.consentimientos.create', $this->paciente))->assertForbidden();
        $this->actingAs($asistente)->post(route('admin.pacientes.consentimientos.store', $this->paciente), $this->datos())->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\ExploracionClinica;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exploración clínica con odontograma (23/11/2026): parte 2 del formato de
 * historia clínica odontológica. La llena la doctora; la asistente no la ve.
 */
class ExploracionClinicaTest extends TestCase
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
            'fecha' => now()->toDateString(),
            'estomatologico' => [
                'labios' => ['normal' => '1', 'hallazgo' => ''],
                'lengua' => ['hallazgo' => 'Lengua saburral'],
                'carrillos' => ['hallazgo' => ''],
            ],
            'atm' => 'Chasquido al abrir del lado derecho',
            'periodontal' => ['sangrado' => '16, 17', 'calculo' => 'todos', 'movilidad' => ''],
            'higiene_oral' => 'regular',
            'oleary_dientes' => '28',
            'oleary_superficies_placa' => '28',
            'pulpares' => [['diente' => '36', 'signos' => 'Dolor al frío', 'diagnostico' => 'Pulpitis reversible'], ['diente' => '', 'signos' => '', 'diagnostico' => '']],
            'radiografica' => [['diente' => '46', 'hallazgo' => 'Lesión periapical']],
            'odontograma' => ['16' => 'cariado', '26' => 'obturado', '36' => 'necesita_endodoncia', '99' => 'cariado'],
            'diagnosticos' => 'Gingivitis generalizada; caries en 16.',
        ];
    }

    public function test_el_expediente_invita_y_el_formulario_trae_todas_las_secciones(): void
    {
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.index', $this->paciente))->assertOk()
            ->assertSee('data-resumen-exploracion', false)
            ->assertSee('Hacer exploración');

        $this->actingAs($this->doctora)->get(route('admin.pacientes.exploracion.create', $this->paciente))->assertOk()
            ->assertSee('Examen de la boca (estomatológico)')
            ->assertSee('Articulación de la mandíbula (ATM)')
            ->assertSee('Encías (periodontal)')
            ->assertSee("Índice de placa de O'Leary", false)
            ->assertSee('Problemas de la pulpa (por diente)')
            ->assertSee('Interpretación radiográfica')
            ->assertSee('data-odontograma', false)
            ->assertSee('data-diente="18"', false)
            ->assertSee('data-diente="85"', false)
            ->assertSee('Diagnósticos definitivos');
    }

    public function test_se_guarda_limpia_lo_vacio_y_calcula_oleary(): void
    {
        $this->actingAs($this->doctora)->post(route('admin.pacientes.exploracion.store', $this->paciente), $this->datos(['odontograma' => ['16' => 'cariado', '26' => 'obturado', '36' => 'necesita_endodoncia']]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.pacientes.historial.index', $this->paciente));

        $ex = ExploracionClinica::sole();
        $this->assertSame($this->doctora->id, $ex->id_doctor);
        // MySQL reordena las claves de los JSON: se compara sin importar el orden.
        $this->assertEqualsCanonicalizing(['labios', 'lengua'], array_keys($ex->estomatologico));
        $this->assertEquals(['sangrado' => '16, 17', 'calculo' => 'todos'], $ex->periodontal);
        $this->assertCount(1, $ex->pulpares);
        $this->assertSame(25.0, $ex->indiceOleary());
        $this->assertSame(['16' => 'cariado', '26' => 'obturado', '36' => 'necesita_endodoncia'], $ex->odontograma);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.index', $this->paciente))->assertOk()
            ->assertSee('25 %')
            ->assertSee('Cariado: 1')
            ->assertSee('Nueva exploración');

        // Una nueva parte del odontograma anterior.
        $this->actingAs($this->doctora)->get(route('admin.pacientes.exploracion.create', $this->paciente))->assertOk()
            ->assertViewHas('exploracion', fn ($nueva) => ! $nueva->exists && $nueva->odontograma === $ex->odontograma);

        // PDF con solo la exploración.
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.pdf', $this->paciente))->assertOk();
    }

    public function test_valores_invalidos_se_rechazan(): void
    {
        $this->actingAs($this->doctora)->post(route('admin.pacientes.exploracion.store', $this->paciente), $this->datos([
            'fecha' => now()->addDay()->toDateString(),
            'higiene_oral' => 'excelente',
            'oleary_dientes' => '10', 'oleary_superficies_placa' => '41',
            'pulpares' => [['diente' => '99', 'signos' => 'x', 'diagnostico' => '']],
            'odontograma' => ['16' => 'pintado'],
        ]))->assertSessionHasErrors(['fecha', 'higiene_oral', 'oleary_superficies_placa', 'pulpares.0.diente', 'odontograma.16']);
        $this->assertSame(0, ExploracionClinica::count());

        // Un diente que no existe en el odontograma se descarta sin error.
        $this->actingAs($this->doctora)->post(route('admin.pacientes.exploracion.store', $this->paciente), $this->datos())->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('99', ExploracionClinica::sole()->odontograma);
    }

    public function test_editar_y_la_bitacora_no_guarda_datos_de_salud(): void
    {
        $this->actingAs($this->doctora)->post(route('admin.pacientes.exploracion.store', $this->paciente), $this->datos());
        $ex = ExploracionClinica::sole();

        $this->actingAs($this->doctora)->get(route('admin.pacientes.exploracion.edit', [$this->paciente, $ex]))->assertOk()
            ->assertSee('Lengua saburral');
        $this->actingAs($this->doctora)->put(route('admin.pacientes.exploracion.update', [$this->paciente, $ex]), $this->datos(['atm' => 'Sin alteraciones']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Sin alteraciones', $ex->fresh()->atm);

        $registros = LogAuditoria::whereIn('accion', ['exploracion_clinica.creada', 'exploracion_clinica.actualizada'])->orderBy('id')->get();
        $this->assertSame(['exploracion_clinica.creada', 'exploracion_clinica.actualizada'], $registros->pluck('accion')->all());
        $this->assertSame(['atm'], $registros->last()->datos_nuevos['campos_modificados']);
        foreach (['saburral', 'Chasquido', 'Pulpitis', 'Gingivitis', 'cariado'] as $dato) {
            $this->assertStringNotContainsString($dato, $registros->toJson(), "La bitácora no debe guardar «{$dato}»");
        }

        // De otro paciente: no se puede abrir con este expediente.
        $otro = Paciente::create(['nombre' => 'Beto', 'apellido' => 'Ruiz', 'telefono' => '5598765432']);
        $this->actingAs($this->doctora)->get(route('admin.pacientes.exploracion.edit', [$otro, $ex]))->assertNotFound();
    }

    public function test_la_asistente_no_la_ve_y_el_doctor_si(): void
    {
        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($doctor)->get(route('admin.pacientes.exploracion.create', $this->paciente))->assertOk();
        $this->actingAs($asistente)->get(route('admin.pacientes.exploracion.create', $this->paciente))->assertForbidden();
        $this->actingAs($asistente)->post(route('admin.pacientes.exploracion.store', $this->paciente), $this->datos())->assertForbidden();
        $this->assertSame(0, ExploracionClinica::count());
    }

    public function test_la_consulta_guarda_los_dientes_tratados(): void
    {
        $this->actingAs($this->doctora)->post(route('admin.pacientes.historial.store', $this->paciente), [
            'fecha_consulta' => now()->toDateString(), 'tipo_consulta' => 'primera_vez', 'motivo_consulta' => 'Dolor',
            'tratamiento_realizado' => 'Resina', 'dientes' => '16, 17',
        ])->assertSessionHasNoErrors();

        $this->assertSame('16, 17', $this->paciente->historialClinico()->sole()->dientes);
    }
}

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
 * cédula profesional de quien atendió (Configuración → Cédulas profesionales,
 * 26/11/2026; antes Mi perfil).
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

        // Con el interruptor encendido, sin cédula explica qué falta.
        config(['dentissa.recetas.exigir_cedula' => true]);
        $this->doctora->update(['cedula_profesional' => null]);
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.receta', [$this->paciente, $this->consulta()]))
            ->assertSessionHas('error', fn (string $mensaje) => str_contains($mensaje, 'cédula profesional') && str_contains($mensaje, 'Configuración'));
    }

    /** 25/11/2026: exigir la cédula está apagado temporalmente (por omisión). */
    public function test_sin_cedula_la_receta_se_imprime_si_no_se_exige(): void
    {
        $this->assertFalse(config('dentissa.recetas.exigir_cedula'), 'Por omisión no se exige');
        $this->doctora->update(['cedula_profesional' => null]);

        $respuesta = $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.receta', [$this->paciente, $this->consulta()]));

        $respuesta->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_la_consulta_de_otro_paciente_no_se_imprime(): void
    {
        $otro = Paciente::create(['nombre' => 'Beto', 'apellido' => 'Paz', 'telefono' => '5533334444']);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.receta', [$otro, $this->consulta()]))->assertNotFound();
    }

    /**
     * 26/11/2026 (decisión del usuario): la cédula se captura en Configuración →
     * Cédulas profesionales, una por doctor; Mi perfil ya no la tiene.
     */
    public function test_la_cedula_se_captura_en_configuracion_y_no_en_mi_perfil(): void
    {
        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor, 'nombre' => 'Luis', 'apellido' => 'Paz']);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente, 'nombre' => 'Rosa', 'apellido' => 'Gil']);

        // Configuración lista a quienes atienden consultas, no a la asistente; la
        // titular primero aunque su nombre vaya después en el alfabeto, y cada
        // fila dice si ya tiene cédula (la titular sí, Luis no).
        $this->actingAs($this->doctora)->get(route('admin.configuracion'))->assertOk()
            ->assertSee('Cédulas profesionales')
            ->assertSeeInOrder(['name="cedulas['.$this->doctora->id.']"', 'name="cedulas['.$doctor->id.']"'], false)
            ->assertDontSee('name="cedulas['.$asistente->id.']"', false)
            ->assertSee('Capturada')
            ->assertSee('Falta 1 cédula.');

        $this->actingAs($this->doctora)->put(route('admin.consultorio.cedulas'), ['cedulas' => [
            $this->doctora->id => '87654321',
            $doctor->id => ' 11223344 ',
            $asistente->id => '99999999', // No atiende consultas: se ignora.
        ]])->assertRedirect(route('admin.configuracion').'#cedulas')->assertSessionHasNoErrors();

        $this->assertSame('87654321', $this->doctora->fresh()->cedula_profesional);
        $this->assertSame('11223344', $doctor->fresh()->cedula_profesional);
        $this->assertNull($asistente->fresh()->cedula_profesional);
        $this->assertSame(2, LogAuditoria::where('accion', 'cedula.actualizada')->count());

        // Formato inválido.
        $this->actingAs($this->doctora)->put(route('admin.consultorio.cedulas'), ['cedulas' => [$doctor->id => '12 34/56']])
            ->assertSessionHasErrors('cedulas.'.$doctor->id);

        // Mi perfil ya no la muestra ni la cambia; la especialidad sí se queda.
        $this->actingAs($this->doctora)->get(route('profile.edit'))->assertOk()
            ->assertDontSee('name="cedula_profesional"', false)
            ->assertSee('name="especialidad"', false);
        $this->actingAs($this->doctora)->patch(route('profile.update'), [
            'nombre' => $this->doctora->nombre, 'apellido' => $this->doctora->apellido, 'telefono' => '5512345678', 'email' => $this->doctora->email,
            'cedula_profesional' => '00000000', 'especialidad' => 'Ortodoncista',
        ])->assertSessionHasNoErrors();
        $this->assertSame('87654321', $this->doctora->fresh()->cedula_profesional);
        $this->assertSame('Ortodoncista', $this->doctora->fresh()->especialidad);

        // Solo quien tiene Configuración (la titular) la cambia.
        $this->actingAs($doctor)->put(route('admin.consultorio.cedulas'), ['cedulas' => [$doctor->id => '55555555']])->assertForbidden();
        $this->assertSame('11223344', $doctor->fresh()->cedula_profesional);
    }
}

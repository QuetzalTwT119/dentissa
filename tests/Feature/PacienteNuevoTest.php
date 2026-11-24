<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registrar paciente sin cita (24/11/2026): para quien llega sin cita o para
 * llenarle la historia clínica antes. El celular no se duplica.
 */
class PacienteNuevoTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    private function datos(array $extra = []): array
    {
        return $extra + ['nombre' => 'Luis', 'apellido' => 'Pérez', 'telefono' => '+52 (755) 123 4567', 'email' => 'luis@example.test', 'acepta_aviso' => '1'];
    }

    public function test_la_lista_ofrece_nuevo_paciente_y_el_formulario_se_abre(): void
    {
        $this->actingAs($this->doctora)->get(route('admin.pacientes.index'))->assertOk()
            ->assertSee('Nuevo paciente')
            ->assertSee(route('admin.pacientes.create'), false);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.create'))->assertOk()
            ->assertSee('data-nuevo-paciente', false)
            ->assertSee('aviso de privacidad');
    }

    public function test_registra_al_paciente_y_abre_su_expediente(): void
    {
        $respuesta = $this->actingAs($this->doctora)->post(route('admin.pacientes.store'), $this->datos());

        $paciente = Paciente::sole();
        $respuesta->assertRedirect(route('admin.pacientes.historial.index', $paciente))->assertSessionHas('success');
        $this->assertSame('7551234567', $paciente->telefono);
        $this->assertSame('Luis Pérez', $paciente->nombre_completo);
        $this->assertNotNull($paciente->aviso_privacidad_aceptado_en);
        $this->assertTrue(LogAuditoria::where('accion', 'paciente.registrado')->where('registro_id', $paciente->id)->exists());
    }

    public function test_no_duplica_el_celular_ni_el_correo(): void
    {
        $ana = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '7551234567', 'email' => 'ana@example.test']);

        $this->actingAs($this->doctora)->from(route('admin.pacientes.create'))->post(route('admin.pacientes.store'), $this->datos(['telefono' => '755 123 4567']))
            ->assertSessionHasErrors(['telefono' => 'Ya está registrado: Ana Ruiz tiene ese celular.'])
            ->assertSessionHas('paciente_existente', $ana->id);

        $this->actingAs($this->doctora)->post(route('admin.pacientes.store'), $this->datos(['telefono' => '5500000000', 'email' => 'ana@example.test']))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Paciente::count());
    }

    public function test_pide_datos_validos_y_el_aviso_de_privacidad(): void
    {
        $this->actingAs($this->doctora)->post(route('admin.pacientes.store'), ['nombre' => '', 'apellido' => '', 'telefono' => '123', 'email' => 'no-es-correo'])
            ->assertSessionHasErrors(['nombre', 'apellido', 'telefono', 'email', 'acepta_aviso']);
        $this->assertSame(0, Paciente::count());
    }

    public function test_el_paciente_registrado_es_el_mismo_al_agendar_en_linea(): void
    {
        $this->actingAs($this->doctora)->post(route('admin.pacientes.store'), $this->datos(['email' => '']));

        // CrearCitaService lo busca por celular cuando no hay correo.
        $this->assertSame(1, Paciente::where('telefono', '7551234567')->count());
        $this->assertNull(Paciente::sole()->email);
    }

    public function test_la_asistente_no_registra_pacientes(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)->get(route('admin.pacientes.create'))->assertForbidden();
        $this->actingAs($asistente)->post(route('admin.pacientes.store'), $this->datos())->assertForbidden();
        $this->assertSame(0, Paciente::count());
    }
}

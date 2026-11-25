<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Permisos de los perfiles Doctor y Asistente (25/11/2026).
 *
 * Criterios de aceptación:
 * - Doctor: agenda, pacientes con expediente, historia clínica y cobros. No
 *   entra a usuarios, configuración, catálogo, contenido, reportes, mensajes
 *   ni control de actividades.
 * - Asistente: agenda, directorio de pacientes (alta y datos de contacto),
 *   cuestionario de historia clínica, cobros y mensajes. No ve el expediente
 *   (consultas, recetas, planes) ni la administración.
 * - Los tres perfiles abren Mi perfil (antes la asistente no podía).
 * - La lista de pacientes no le muestra enlaces al expediente (el alta que la
 *   lleva al cuestionario se prueba en PacienteNuevoTest).
 * - Usuarios → Nuevo usuario explica qué puede hacer cada rol.
 */
class PermisosPerfilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Cobros está apagado por omisión (DENTISSA_MODULO_PAGOS); aquí se revisa encendido.
        config(['dentissa.modulos.pagos' => true]);
    }

    public function test_el_doctor_ve_lo_clinico_y_no_la_administracion(): void
    {
        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor]);
        $paciente = $this->paciente();

        foreach ([
            route('dashboard'), route('profile.edit'), route('citas.index'), route('admin.pacientes.index'),
            route('admin.pacientes.historial.index', $paciente), route('admin.pacientes.historia.edit', $paciente),
            route('admin.pagos.index'),
        ] as $url) {
            $this->actingAs($doctor)->get($url)->assertOk();
        }

        foreach ([
            route('admin.usuarios.index'), route('admin.configuracion'), route('admin.tratamientos.index'),
            route('admin.ofertas.index'), route('admin.reportes.index'), route('admin.mensajes.index'),
            route('admin.auditoria.index'),
        ] as $url) {
            $this->actingAs($doctor)->get($url)->assertForbidden();
        }
    }

    public function test_la_asistente_atiende_recepcion_sin_ver_el_expediente(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $paciente = $this->paciente();

        foreach ([
            route('dashboard'), route('profile.edit'), route('citas.index'), route('admin.pacientes.index'),
            route('admin.pacientes.create'), route('admin.pacientes.edit', $paciente),
            route('admin.pacientes.historia.edit', $paciente), route('admin.pagos.index'), route('admin.mensajes.index'),
        ] as $url) {
            $this->actingAs($asistente)->get($url)->assertOk();
        }

        foreach ([
            route('admin.pacientes.historial.index', $paciente), route('admin.pacientes.historial.create', $paciente),
            route('admin.pacientes.planes.create', $paciente), route('admin.usuarios.index'), route('admin.configuracion'),
            route('admin.reportes.index'), route('admin.ofertas.index'), route('admin.auditoria.index'),
        ] as $url) {
            $this->actingAs($asistente)->get($url)->assertForbidden();
        }

        // La lista no le ofrece abrir el expediente.
        $this->actingAs($asistente)->get(route('admin.pacientes.index'))
            ->assertSee($paciente->nombre)
            ->assertDontSee('data-ver-historial', false)
            ->assertDontSee(route('admin.pacientes.historial.index', $paciente), false);
    }

    public function test_el_formulario_de_usuario_explica_los_permisos_de_cada_rol(): void
    {
        $titular = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

        $this->actingAs($titular)->get(route('admin.usuarios.create'))
            ->assertOk()
            ->assertSee('data-permisos-rol', false)
            ->assertSee(RolUsuario::Asistente->descripcion());
    }

    public function test_la_descripcion_de_cada_rol_coincide_con_el_mapa_de_permisos(): void
    {
        // Si alguien cambia config/permisos.php, esta prueba recuerda actualizar el texto.
        $this->assertTrue(RolUsuario::Doctor->puede('pacientes'));
        $this->assertFalse(RolUsuario::Doctor->puede('reportes'));
        $this->assertFalse(RolUsuario::Doctor->puede('usuarios'));
        $this->assertFalse(RolUsuario::Asistente->puede('pacientes'));
        $this->assertTrue(RolUsuario::Asistente->puede('directorio_pacientes'));
        $this->assertTrue(RolUsuario::Asistente->puede('pagos'));
        $this->assertTrue(RolUsuario::Asistente->puede('mensajes'));
        foreach (RolUsuario::cases() as $rol) {
            $this->assertTrue($rol->puede('cuenta'), "{$rol->value} debe poder abrir Mi perfil");
        }
    }

    private function paciente(): Paciente
    {
        return Paciente::create([
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '5511223344',
            'email' => 'ana@example.test',
        ]);
    }
}

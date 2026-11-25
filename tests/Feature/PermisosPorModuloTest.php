<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Paciente;
use App\Models\User;
use App\Support\Permisos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Roles super_admin / doctor / asistente (22/11/2026). Todo se decide en
 * config/permisos.php; estas pruebas cuidan que el mapa y la app coincidan.
 */
class PermisosPorModuloTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Pagos está oculto por omisión (24/11/2026); aquí se revisa con todo encendido.
        config(['dentissa.modulos.pagos' => true]);
        $this->paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5511223344']);
    }

    private Paciente $paciente;

    /** URL de la página de un módulo; las del expediente llevan al paciente de prueba. */
    private function url(string $pagina): string
    {
        return str_starts_with($pagina, 'admin.pacientes.historial') ? route($pagina, $this->paciente) : route($pagina);
    }

    /** Páginas GET representativas de cada módulo (sin parámetros). */
    private const PAGINAS = [
        'inicio' => 'dashboard',
        'cuenta' => 'profile.edit',
        'dos_pasos' => 'dos-pasos.show',
        'notificaciones' => 'notificaciones.index',
        'agenda' => 'citas.index',
        'asistente_agenda' => 'asistente-agenda.create',
        'historia_clinica' => 'admin.historias.index',
        // 25/11/2026: el directorio (lista y alta) se separó del expediente.
        'directorio_pacientes' => 'admin.pacientes.index',
        'pacientes' => 'admin.pacientes.historial.index',
        'usuarios' => 'admin.usuarios.index',
        'reportes' => 'admin.reportes.index',
        'pagos' => 'admin.pagos.index',
        'mensajes' => 'admin.mensajes.index',
        'control_actividades' => 'admin.auditoria.index',
        'catalogo' => 'admin.servicios.index',
        'contenido' => 'admin.ofertas.index',
        'configuracion' => 'admin.configuracion',
    ];

    public function test_toda_ruta_protegida_tiene_nombre_y_modulo(): void
    {
        $sinModulo = [];

        foreach (Route::getRoutes() as $ruta) {
            if (! in_array('permiso', $ruta->gatherMiddleware(), true)) {
                continue;
            }
            if (Permisos::moduloDeRuta($ruta->getName()) === null) {
                $sinModulo[] = implode('|', $ruta->methods()).' '.$ruta->uri().' ('.($ruta->getName() ?? 'sin nombre').')';
            }
        }

        $this->assertSame([], $sinModulo, 'Rutas del panel sin módulo en config/permisos.php (se negarían a todos).');
        // Y todo módulo de una página de esta prueba existe en el mapa.
        $this->assertEqualsCanonicalizing(array_keys(self::PAGINAS), array_keys(config('permisos.modulos')));
    }

    public function test_cada_rol_entra_solo_a_sus_modulos(): void
    {
        // 25/11/2026: el doctor, solo lo clínico; la asistente, recepción.
        $roles = [
            RolUsuario::SuperAdmin->value => array_keys(self::PAGINAS),
            RolUsuario::Doctor->value => ['inicio', 'cuenta', 'dos_pasos', 'notificaciones', 'agenda', 'asistente_agenda', 'directorio_pacientes', 'pacientes', 'historia_clinica', 'pagos'],
            RolUsuario::Asistente->value => ['inicio', 'cuenta', 'dos_pasos', 'notificaciones', 'agenda', 'asistente_agenda', 'directorio_pacientes', 'historia_clinica', 'pagos', 'mensajes'],
        ];

        foreach ($roles as $rol => $permitidos) {
            $usuario = User::factory()->create(['rol' => $rol]);

            foreach (self::PAGINAS as $modulo => $pagina) {
                $respuesta = $this->actingAs($usuario)->get($this->url($pagina));
                $esperado = in_array($modulo, $permitidos, true);

                $this->assertSame($esperado, $respuesta->status() === 200, "$rol → $pagina (".$respuesta->status().')');
                if (! $esperado) {
                    $this->assertSame(403, $respuesta->status(), "$rol → $pagina");
                }
            }
        }
    }

    public function test_el_menu_solo_muestra_lo_que_cada_rol_puede_abrir(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $html = $this->actingAs($asistente)->get(route('dashboard'))->assertOk()->getContent();

        foreach (['admin.usuarios.index', 'admin.servicios.index', 'admin.ofertas.index', 'admin.configuracion', 'admin.reportes.index', 'ofertas.index', 'galeria.index'] as $pagina) {
            $this->assertStringNotContainsString('href="'.route($pagina).'"', $html, "La asistente no debe ver el enlace a $pagina");
        }
        // Agenda, su asistente y la campana (24/11/2026); directorio, perfil, cobros y mensajes (25/11/2026).
        foreach (['citas.index', 'asistente-agenda.create', 'notificaciones.index', 'admin.pacientes.index', 'profile.edit', 'admin.pagos.index', 'admin.mensajes.index'] as $pagina) {
            $this->assertStringContainsString('href="'.route($pagina), $html, "La asistente debe ver $pagina");
        }
        $this->assertStringContainsString('action="'.route('logout').'"', $html, 'Cerrar sesión siempre disponible');

        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor]);
        $html = $this->actingAs($doctor)->get(route('dashboard'))->assertOk()->getContent();
        foreach (['citas.index', 'admin.pacientes.index', 'profile.edit', 'admin.pagos.index'] as $pagina) {
            $this->assertStringContainsString('href="'.route($pagina), $html, "El doctor debe ver $pagina");
        }
        foreach (['admin.usuarios.index', 'admin.servicios.index', 'admin.configuracion', 'admin.reportes.index', 'admin.auditoria.index'] as $pagina) {
            $this->assertStringNotContainsString('href="'.route($pagina).'"', $html, "El doctor no debe ver $pagina");
        }
    }

    public function test_un_modulo_inexistente_nunca_se_concede(): void
    {
        $this->assertFalse(RolUsuario::SuperAdmin->puede('modulo_que_no_existe'));
        $this->assertTrue(RolUsuario::SuperAdmin->puede('usuarios'));
        $this->assertFalse(RolUsuario::Asistente->puede('pacientes'));
    }

    public function test_la_cuenta_titular_solo_la_toca_otra_titular(): void
    {
        $titular = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor, 'activo' => true]);

        // 25/11/2026: el doctor ya no administra usuarios: ni la desactiva, ni la elimina, ni crea cuentas.
        $this->actingAs($doctor)->patch(route('admin.usuarios.activo.toggle', $titular))->assertForbidden();
        $this->actingAs($doctor)->delete(route('admin.usuarios.destroy', $titular))->assertForbidden();
        $this->assertTrue($titular->fresh()->activo);

        $this->actingAs($doctor)->post(route('admin.usuarios.store'), [
            'nombre' => 'Otra', 'apellido' => 'Titular', 'email' => 'otra@example.test', 'telefono' => '7551020089',
            'rol' => 'super_admin', 'password' => 'Clinica2026A', 'password_confirmation' => 'Clinica2026A',
        ])->assertForbidden();
        $this->actingAs($doctor)->get(route('admin.usuarios.create'))->assertForbidden();

        // La titular sí puede crear un doctor y otra titular.
        $this->actingAs($titular)->get(route('admin.usuarios.create'))->assertSee('value="super_admin"', false);
    }
}

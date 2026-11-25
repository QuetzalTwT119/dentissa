<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\BloqueoAgenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ajustes del panel para la presentación (25/11/2026).
 *
 * Criterios de aceptación:
 * - Por omisión (producción) están apagados el Asistente de agenda, Mensajes
 *   de contacto y la verificación en dos pasos: no salen en el menú ni en Mi
 *   perfil, sus rutas dan 403 y los dos pasos no se exigen al entrar.
 * - El menú dice Inicio · Agenda · Pacientes · Administración · Catálogo ·
 *   Sitio web · Configuración, y cada pantalla se titula igual que su enlace.
 * - «Próximos bloqueos» se pagina de 5 en 5 y vuelve a la sección.
 *
 * phpunit.xml enciende esos módulos para el resto de la suite; aquí se apagan
 * como en producción.
 */
class MenuDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'dentissa.modulos.asistente_agenda' => false,
            'dentissa.modulos.mensajes' => false,
            'dentissa.modulos.dos_pasos' => false,
        ]);
    }

    public function test_los_valores_por_omision_los_dejan_apagados(): void
    {
        // Lo que lee config/dentissa.php sin variables en Railway.
        $codigo = file_get_contents(config_path('dentissa.php'));
        $this->assertStringContainsString("env('DENTISSA_MODULO_ASISTENTE_AGENDA', false)", $codigo);
        $this->assertStringContainsString("env('DENTISSA_MODULO_MENSAJES', false)", $codigo);
        $this->assertStringContainsString("env('DENTISSA_MODULO_DOS_PASOS', false)", $codigo);
    }

    public function test_los_modulos_apagados_no_se_ven_ni_se_abren(): void
    {
        config(['dentissa.seguridad.dos_pasos_admin_obligatorio' => true]);
        $titular = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

        // Aunque los dos pasos sean obligatorios, apagados no se exigen.
        $html = $this->actingAs($titular)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('href="'.route('asistente-agenda.create').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.mensajes.index').'"', $html);

        $this->actingAs($titular)->get(route('profile.edit'))->assertOk()
            ->assertDontSee('Verificación en dos pasos')
            ->assertDontSee(route('dos-pasos.show'), false);

        foreach ([route('asistente-agenda.create'), route('admin.mensajes.index'), route('dos-pasos.show')] as $url) {
            $this->actingAs($titular)->get($url)->assertForbidden();
        }
    }

    public function test_el_menu_y_los_titulos_dicen_lo_mismo(): void
    {
        $titular = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $html = $this->actingAs($titular)->get(route('dashboard'))->assertOk()->getContent();

        foreach (['Inicio', 'Agenda', 'Pacientes', 'Administración', 'Catálogo', 'Especialidades', 'Sitio web', 'Configuración'] as $texto) {
            $this->assertStringContainsString($texto, $html, "El menú debe decir «{$texto}»");
        }
        foreach (['>Gestión<', '>Contenido<', '>Servicios<'] as $viejo) {
            $this->assertStringNotContainsString($viejo, $html, "El menú ya no dice {$viejo}");
        }

        $agenda = $this->actingAs($titular)->get(route('citas.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#<h1[^>]*>\s*Agenda\s*</h1>#', $agenda);
        $this->actingAs($titular)->get(route('admin.pacientes.index'))->assertSee('Pacientes')->assertDontSee('Directorio de Pacientes');
        $this->actingAs($titular)->get(route('admin.servicios.index'))->assertSee('Especialidades');
    }

    public function test_proximos_bloqueos_se_paginan_de_cinco_en_cinco(): void
    {
        $titular = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        foreach (range(1, 7) as $dia) {
            BloqueoAgenda::create([
                'fecha_inicio' => now()->addDays($dia * 2)->toDateString(),
                'fecha_fin' => now()->addDays($dia * 2)->toDateString(),
                'motivo' => "Bloqueo {$dia}",
            ]);
        }

        $pagina1 = $this->actingAs($titular)->get(route('admin.configuracion'))->assertOk()->getContent();
        $this->assertSame(5, substr_count($pagina1, 'data-bloqueo>'));
        $this->assertStringContainsString('(7)', $pagina1);
        $this->assertStringContainsString('bloqueos=2#bloqueos', html_entity_decode($pagina1));

        $pagina2 = $this->actingAs($titular)->get(route('admin.configuracion', ['bloqueos' => 2]))->assertOk()->getContent();
        $this->assertSame(2, substr_count($pagina2, 'data-bloqueo>'));
    }
}

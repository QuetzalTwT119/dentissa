<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavegacionResponsivaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_navegacion_interna_conserva_la_marca_y_agrupa_secciones_por_funcion(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('citas.index'))
            ->assertOk()
            ->assertSee('Dentissa', false)
            ->assertSee('Administración')
            ->assertSee('Inicio')
            ->assertSee('Agenda')
            ->assertSee('Gestión')
            ->assertSee('id="mobile-navigation-menu"', false)
            ->assertSee('overflow-y-auto overscroll-contain', false)
            ->assertSee('hidden min-w-0 flex-1 items-center justify-center gap-1 2xl:flex', false)
            ->assertSee('aria-controls="mobile-navigation-menu"', false)
            ->assertSee('Cerrar menú de navegación', false)
            ->assertSee('Pacientes')
            ->assertSee('Usuarios')
            ->assertSee('Reportes')
            ->assertSee('Auditoría')
            ->assertSee('Catálogo')
            ->assertSee('Contenido')
            ->assertSee('Configuración')
            ->assertSee(route('admin.auditoria.index'));
    }

    public function test_la_lista_de_pacientes_se_apila_en_pantallas_angostas_sin_desplazarse_de_lado(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        // Tabla corta del modo "Mixta" (ver TablasMixtasTest).
        $this->actingAs($admin)
            ->get(route('admin.pacientes.index'))
            ->assertOk()
            ->assertSee('tabla-apilable', false)
            ->assertDontSee('min-w-[760px]', false)
            ->assertSee('aria-label="Buscar pacientes por nombre, correo o teléfono"', false)
            ->assertSee('sm:w-72', false);
    }

    public function test_el_formulario_de_usuario_apila_campos_en_movil(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.create'))
            ->assertOk()
            ->assertSee('grid grid-cols-1 gap-4 sm:grid-cols-2', false);
    }

    public function test_el_encabezado_de_testimonios_apila_filtro_y_accion_en_movil(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.testimonios.index'))
            ->assertOk()
            ->assertSee('flex flex-col items-stretch gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6', false)
            ->assertSee('class="flex flex-wrap items-center gap-3"', false);
    }

    public function test_los_formularios_de_adjuntos_apilan_archivo_y_boton_en_pantallas_angostas(): void
    {
        $historial = file_get_contents(resource_path('views/admin/historial/edit.blade.php'));
        $caso = file_get_contents(resource_path('views/admin/casos/edit.blade.php'));

        $this->assertStringContainsString('flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4', $historial);
        $this->assertStringContainsString('flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4', $caso);
        $this->assertStringContainsString('min-w-0 flex-1', $historial);
        $this->assertStringContainsString('min-w-0 flex-1', $caso);
    }
}

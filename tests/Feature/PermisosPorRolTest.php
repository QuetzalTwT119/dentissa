<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermisosPorRolTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_asistente_no_puede_consultar_modulos_reservados_a_administracion(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $rutasAdministrativas = [
            'admin.auditoria.index',
            'admin.servicios.index',
            'admin.pacientes.index',
            'admin.usuarios.index',
            'admin.reportes.index',
            'admin.horarios.edit',
            'admin.galeria.index',
            'admin.casos.index',
            'admin.preguntas-frecuentes.index',
            'admin.testimonios.index',
            'admin.ofertas.index',
            'admin.mensajes.index',
        ];

        foreach ($rutasAdministrativas as $nombreRuta) {
            $this->actingAs($asistente)
                ->get(route($nombreRuta))
                ->assertForbidden();
        }
    }

    public function test_un_asistente_no_puede_modificar_configuracion_administrativa(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->put(route('admin.horarios.update'), [])
            ->assertForbidden();
    }

    public function test_horarios_muestra_campos_editables_de_apertura_cierre_y_capacidad(): void
    {
        // Regresión: comillas dobles anidadas en old("...") impedían que Blade
        // compilara <x-text-input> y los campos no aparecían en pantalla.
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.horarios.edit'))
            ->assertOk()
            ->assertDontSee('<x-text-input', false)
            ->assertSee('name="dias[0][hora_apertura]"', false)
            ->assertSee('name="dias[0][hora_cierre]"', false)
            ->assertSee('name="dias[0][capacidad_simultanea]"', false);
    }

    public function test_lista_de_usuarios_tiene_contexto_correcto_y_filtros_accesibles(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Gestiona las cuentas y los roles del personal que accede a la plataforma.')
            ->assertSee('aria-label="Filtrar por rol"', false)
            ->assertSee('aria-label="Filtrar por estado"', false);
    }

    public function test_un_visitante_es_enviado_al_login_antes_de_entrar_a_administracion(): void
    {
        $this->get(route('admin.reportes.index'))
            ->assertRedirect(route('login'));
    }
}

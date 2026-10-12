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
            'admin.atencion-oportuna.index',
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

    public function test_un_visitante_es_enviado_al_login_antes_de_entrar_a_administracion(): void
    {
        $this->get(route('admin.reportes.index'))
            ->assertRedirect(route('login'));
    }
}

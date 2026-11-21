<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_the_activity_log(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('Auditoría')
            ->assertSee('Solo lectura. Se registran acciones de cuentas del personal');
    }

    public function test_assistant_cannot_see_the_activity_log(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->get(route('admin.auditoria.index'))
            ->assertForbidden();
    }

    public function test_authenticated_changes_are_registered_without_sensitive_values(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin);
        $equipo = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $registro = LogAuditoria::query()
            ->where('id_usuario', $admin->id)
            ->where('tabla', 'users')
            ->where('registro_id', $equipo->id)
            ->first();

        $this->assertNotNull($registro);
        $this->assertSame('creado', $registro->accion);
        $this->assertArrayHasKey('campos_modificados', $registro->datos_nuevos);
        $this->assertNotContains($equipo->email, $registro->datos_nuevos['campos_modificados']);
    }
}

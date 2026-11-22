<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 22/11/2026: la doctora se quitó a sí misma el rol de administración en
 * Usuarios → Editar y se quedó sin acceso a todo el panel (era la única
 * administradora). Ahora el rol propio no se cambia y nunca se degrada al
 * último administrador activo.
 */
class RolPropioTest extends TestCase
{
    use RefreshDatabase;

    private function datos(User $u, string $rol): array
    {
        return ['nombre' => $u->nombre, 'apellido' => $u->apellido, 'email' => $u->email, 'telefono' => '7551020089', 'rol' => $rol];
    }

    public function test_nadie_cambia_su_propio_rol(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);
        User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]); // hay otro admin: aun así no

        $this->actingAs($admin)->from(route('admin.usuarios.edit', $admin))
            ->put(route('admin.usuarios.update', $admin), $this->datos($admin, 'asistente'))
            ->assertSessionHasErrors(['rol' => 'No puedes cambiar tu propio rol. Otro administrador puede hacerlo.']);
        $this->assertSame(RolUsuario::Admin, $admin->fresh()->rol);

        // En la pantalla no hay menú de rol para la cuenta propia.
        $this->actingAs($admin)->get(route('admin.usuarios.edit', $admin))->assertOk()
            ->assertSee('data-rol-propio', false)
            ->assertDontSee('<select id="rol"', false);

        // Guardar sus otros datos sí funciona.
        $this->actingAs($admin)->put(route('admin.usuarios.update', $admin), ['nombre' => 'Melissa'] + $this->datos($admin, 'admin'))
            ->assertSessionHasNoErrors();
        $this->assertSame('Melissa', $admin->fresh()->nombre);
    }

    public function test_no_se_degrada_al_ultimo_administrador_activo(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);
        $otroAdmin = User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => false]); // inactivo: no cuenta

        // Con un solo admin activo, bajar al otro (inactivo) sí se puede; al activo, no (es él mismo).
        $this->actingAs($admin)->put(route('admin.usuarios.update', $otroAdmin), $this->datos($otroAdmin, 'asistente'))
            ->assertSessionHasNoErrors();

        // Dos admins activos: uno puede bajar al otro.
        $segundo = User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);
        $this->actingAs($admin)->put(route('admin.usuarios.update', $segundo), $this->datos($segundo, 'asistente'))
            ->assertSessionHasNoErrors();
        $this->assertSame(RolUsuario::Asistente, $segundo->fresh()->rol);

        // Y ese segundo, ya de vuelta como admin, no puede dejar a la clínica sin administradores.
        $segundo->update(['rol' => RolUsuario::Admin]);
        $admin->update(['activo' => false]);
        $tercero = User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);
        $tercero->update(['activo' => true]);
        $segundo->update(['activo' => false]);
        $this->actingAs($tercero)->put(route('admin.usuarios.update', $tercero), $this->datos($tercero, 'asistente'))
            ->assertSessionHasErrors('rol');
        $this->assertSame(RolUsuario::Admin, $tercero->fresh()->rol);
    }
}

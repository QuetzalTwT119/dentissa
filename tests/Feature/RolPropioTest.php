<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\HistorialClinico;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Módulo Usuarios (22/11/2026, decisión del usuario): solo crear, desactivar y
 * eliminar. Antes también se editaba y por ahí la doctora se quitó a sí misma
 * el rol de administración (H-55). Eliminar nunca borra historial clínico.
 */
class RolPropioTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuarios_ya_no_se_editan(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente, 'activo' => true]);

        // Sin rutas de editar ni guardar cambios: nadie cambia un rol (ni el propio).
        $this->actingAs($admin)->get('/admin/usuarios/'.$asistente->id.'/edit')->assertNotFound();
        $this->actingAs($admin)->put('/admin/usuarios/'.$admin->id, ['rol' => 'asistente'])->assertStatus(405);
        $this->assertSame(RolUsuario::SuperAdmin, $admin->fresh()->rol);

        $html = $this->actingAs($admin)->get(route('admin.usuarios.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('aria-label="Editar', $html);
        $this->assertStringContainsString('action="'.route('admin.usuarios.destroy', $asistente).'"', $html);
    }

    public function test_eliminar_sin_consultas_si_y_con_consultas_o_la_propia_no(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $nueva = User::factory()->create(['rol' => RolUsuario::Asistente, 'nombre' => 'Laura', 'apellido' => 'Gómez']);
        $doctora = User::factory()->create(['rol' => RolUsuario::Asistente, 'nombre' => 'Rosa', 'apellido' => 'Díaz']);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Martínez', 'telefono' => '5512345678']);
        HistorialClinico::create(['id_paciente' => $paciente->id, 'id_doctor' => $doctora->id, 'fecha_consulta' => now()->toDateString(), 'motivo_consulta' => 'Revisión', 'tipo_consulta' => 'primera_vez']);

        // En la lista: papelera apagada con su motivo para la propia y para quien registró consultas.
        $html = $this->actingAs($admin)->get(route('admin.usuarios.index'))->getContent();
        $this->assertStringContainsString('Es tu cuenta: no puedes eliminarla.', $html);
        $this->assertStringContainsString('Registró 1 consulta clínica', $html);
        $this->assertStringNotContainsString('action="'.route('admin.usuarios.destroy', $admin).'"', $html);
        $this->assertStringNotContainsString('action="'.route('admin.usuarios.destroy', $doctora).'"', $html);

        // Sin consultas: se elimina y queda en Control de actividades.
        $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $nueva))
            ->assertRedirect(route('admin.usuarios.index'))->assertSessionHas('success', 'Usuario Laura Gómez eliminado.');
        $this->assertNull(User::find($nueva->id));
        $this->assertDatabaseHas('logs_auditoria', ['accion' => 'usuario.eliminado', 'registro_id' => $nueva->id]);

        // Con consultas: el servidor lo rechaza aunque llegue la petición; el historial sigue.
        $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $doctora))
            ->assertSessionHas('error', 'No se puede eliminar a Rosa Díaz: registró 1 consulta clínica. Desactívalo para conservarlas.');
        $this->assertNotNull(User::find($doctora->id));
        $this->assertSame(1, HistorialClinico::count());

        // La propia, nunca.
        $this->actingAs($admin)->delete(route('admin.usuarios.destroy', $admin))->assertStatus(422);
        $this->assertNotNull(User::find($admin->id));
    }

    public function test_la_asistente_no_entra_a_usuarios(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente, 'activo' => true]);
        $otra = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)->delete(route('admin.usuarios.destroy', $otra))->assertForbidden();
        $this->assertNotNull(User::find($otra->id));
    }
}

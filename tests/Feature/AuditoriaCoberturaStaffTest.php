<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaCoberturaStaffTest extends TestCase
{
    use RefreshDatabase;

    public function test_alta_de_usuario_guarda_actor_y_datos_seguros_sin_contrasena(): void
    {
        $admin = User::factory()->create([
            'nombre' => 'Melissa',
            'apellido' => 'Admin',
            'rol' => RolUsuario::Admin,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                'nombre' => 'Ana',
                'apellido' => 'Asistente',
                'email' => 'ana@example.test',
                'telefono' => '5551234567',
                'rol' => RolUsuario::Asistente->value,
                'password' => 'Clave-Segura-2026!',
                'password_confirmation' => 'Clave-Segura-2026!',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $usuario = User::where('email', 'ana@example.test')->firstOrFail();
        $evento = LogAuditoria::where('accion', 'usuario.creado')->firstOrFail();

        $this->assertSame($admin->id, $evento->actor_user_id);
        $this->assertSame('Melissa Admin', $evento->actor_nombre);
        $this->assertSame(RolUsuario::Admin->value, $evento->actor_rol);
        $this->assertSame($usuario->id, $evento->registro_id);
        $this->assertSame('asistente', $evento->datos_nuevos['rol']);
        $this->assertArrayNotHasKey('password', $evento->datos_nuevos);
        $this->assertStringNotContainsString('Clave-Segura-2026!', json_encode($evento->toArray()));
    }

    public function test_actualizacion_del_perfil_de_asistente_se_registra_sin_datos_de_contrasena(): void
    {
        $asistente = User::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'Inicial',
            'rol' => RolUsuario::Asistente,
        ]);

        $this->actingAs($asistente)
            ->patch(route('profile.update'), [
                'nombre' => 'Ana',
                'apellido' => 'Actualizada',
                'email' => $asistente->email,
                'telefono' => '5551112233',
            ])
            ->assertRedirect(route('profile.edit'));

        $evento = LogAuditoria::where('accion', 'perfil.actualizado')->firstOrFail();

        $this->assertSame($asistente->id, $evento->actor_user_id);
        $this->assertSame('asistente', $evento->actor_rol);
        $this->assertSame('Inicial', $evento->datos_anteriores['apellido']);
        $this->assertSame('Actualizada', $evento->datos_nuevos['apellido']);
        $this->assertArrayNotHasKey('password', $evento->datos_nuevos);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 22/11/2026 (encontrado por el usuario): al crear o cambiar una contraseña no
 * se podía ver lo escrito y solo al guardar decía "no coincide". Ahora las
 * pantallas que piden contraseña nueva (Nuevo usuario y Mi perfil) usan las
 * piezas globales con 👁 y aviso en vivo.
 */
class CampoContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_formularios_de_contrasena_dejan_verla_y_avisan_si_coincide(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        foreach ([route('admin.usuarios.create'), route('profile.edit')] as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('data-contrasena-nueva', $html, $url);
            $this->assertStringContainsString('data-aviso-coincide', $html, $url);
            $this->assertStringContainsString('Todavía no coinciden', $html, $url);
            $this->assertStringContainsString('Mostrar contraseña', $html, $url);
            $this->assertStringContainsString('Mínimo 10 caracteres, con mayúsculas, minúsculas y números.', $html, $url);
        }
    }

    public function test_si_no_coinciden_al_crear_se_rechaza(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)->post(route('admin.usuarios.store'), [
            'nombre' => 'Laura', 'apellido' => 'Gómez', 'email' => 'laura@example.test', 'telefono' => '7551020089',
            'rol' => 'asistente', 'password' => 'Clinica2026A', 'password_confirmation' => 'Clinica2026B',
        ])->assertSessionHasErrors('password');

        $this->assertNull(User::where('email', 'laura@example.test')->first());
    }
}

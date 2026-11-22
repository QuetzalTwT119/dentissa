<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 22/11/2026 (encontrado por el usuario): al crear o cambiar una contraseña no
 * se podía ver lo escrito y solo al guardar decía "no coincide"; en Editar
 * usuario el título de la sección quedaba junto a "Rol", desalineado.
 */
class CampoContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_formularios_de_contrasena_dejan_verla_y_avisan_si_coincide(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $pantallas = [
            route('admin.usuarios.create'),
            route('admin.usuarios.edit', $asistente),
            route('profile.edit'),
        ];

        foreach ($pantallas as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('data-contrasena-nueva', $html, $url);
            $this->assertStringContainsString('data-aviso-coincide', $html, $url);
            $this->assertStringContainsString('Todavía no coinciden', $html, $url);
            $this->assertStringContainsString('Mostrar contraseña', $html, $url);
            $this->assertStringContainsString('Mínimo 10 caracteres, con mayúsculas, minúsculas y números.', $html, $url);
        }

        // Editar usuario: la sección de contraseña va en su propio renglón.
        $this->actingAs($admin)->get(route('admin.usuarios.edit', $asistente))
            ->assertSee('data-seccion-contrasena', false)
            ->assertSee('Déjalo en blanco para mantener la contraseña actual.')
            ->assertDontSee('Cambio de contraseña (opcional)');
    }

    public function test_dejarla_en_blanco_no_cambia_la_contrasena_y_si_no_coincide_se_rechaza(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $anterior = $asistente->password;
        $datos = ['nombre' => $asistente->nombre, 'apellido' => $asistente->apellido, 'email' => $asistente->email, 'telefono' => '7551020089', 'rol' => 'asistente'];

        $this->actingAs($admin)->put(route('admin.usuarios.update', $asistente), $datos + ['password' => '', 'password_confirmation' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame($anterior, $asistente->fresh()->password);

        $this->actingAs($admin)->put(route('admin.usuarios.update', $asistente), $datos + ['password' => 'Clinica2026A', 'password_confirmation' => 'Clinica2026B'])
            ->assertSessionHasErrors('password');
        $this->assertSame($anterior, $asistente->fresh()->password);
    }
}

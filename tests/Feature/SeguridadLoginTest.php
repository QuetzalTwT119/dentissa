<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Revisión de seguridad de /login en producción (27/11/2026, H-113).
 */
class SeguridadLoginTest extends TestCase
{
    use RefreshDatabase;

    /** 5 intentos por correo ya existían; ahora también 20 por minuto por IP, con correos distintos. */
    public function test_el_inicio_de_sesion_limita_intentos_por_ip_con_correos_distintos(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->post('/login', ['email' => "persona{$i}@example.test", 'password' => 'Incorrecta123'])
                ->assertStatus(302);
        }

        $this->post('/login', ['email' => 'persona21@example.test', 'password' => 'Incorrecta123'])
            ->assertStatus(429);
    }

    /** Sin límite se podían mandar muchos correos de recuperación y gastar la cuota de Gmail. */
    public function test_recuperar_contrasena_tiene_limite_por_minuto(): void
    {
        Notification::fake();

        for ($i = 1; $i <= 6; $i++) {
            $this->post('/forgot-password', ['email' => "nadie{$i}@example.test"])->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => 'nadie7@example.test'])->assertStatus(429);
    }

    /** Cambiar la contraseña con el enlace también tiene límite. */
    public function test_restablecer_contrasena_tiene_limite_por_minuto(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $this->post('/reset-password', ['token' => 'x', 'email' => 'a@example.test', 'password' => 'Nueva12345A', 'password_confirmation' => 'Nueva12345A'])
                ->assertStatus(302);
        }

        $this->post('/reset-password', ['token' => 'x', 'email' => 'a@example.test', 'password' => 'Nueva12345A', 'password_confirmation' => 'Nueva12345A'])
            ->assertStatus(429);
    }

    /** No anunciar la versión de PHP; los demás encabezados de seguridad siguen. */
    public function test_no_se_anuncia_la_version_de_php(): void
    {
        $respuesta = $this->get('/login')->assertOk();

        $this->assertFalse($respuesta->headers->has('X-Powered-By'));
        $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertStringContainsString('expose_php = Off', file_get_contents(base_path('Dockerfile')));
    }

    /** Ya existía: la contraseña nueva pide 10 caracteres con mayúsculas, minúsculas y números. */
    public function test_la_contrasena_nueva_debe_ser_fuerte(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->from('/profile')
            ->put('/password', ['current_password' => 'password', 'password' => 'corta12', 'password_confirmation' => 'corta12'])
            ->assertSessionHasErrorsIn('updatePassword', 'password');
    }
}

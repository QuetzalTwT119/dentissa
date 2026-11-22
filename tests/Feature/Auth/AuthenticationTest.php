<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Iniciar sesión')
            ->assertSee('Melissa López N.')
            ->assertSee('Cada cita merece una atención bien coordinada.')
            ->assertSee('hero-inicio.jpg')
            // Marca global sobre la foto (22/11/2026).
            ->assertSee('data-marca', false)
            ->assertSee('Dra. Melissa López')
            ->assertDontSee('rounded-2xl border border-white/20 bg-white/95')
            ->assertSee('Acceso privado del consultorio')
            ->assertSee('Acceso · Melissa López N. — Odontología Integral')
            ->assertDontSee('focus:ring-indigo-500')
            ->assertSee('action="'.route('login').'"', false)
            ->assertSee('name="remember"', false)
            ->assertSee(route('password.request'), false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_request_a_persistent_login(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}

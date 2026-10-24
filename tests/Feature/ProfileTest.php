<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'nombre' => 'Test User',
                'apellido' => 'Prueba',
                'telefono' => '5512345678',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->nombre);
        $this->assertSame('Prueba', $user->apellido);
        $this->assertSame('5512345678', $user->telefono);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'nombre' => 'Test User',
                'apellido' => 'Prueba',
                'telefono' => '5512345678',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_deactivate_their_account_without_deleting_the_user_record(): void
    {
        $user = User::factory()->create(['rol' => \App\Enums\RolUsuario::Asistente]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNotNull($user->fresh());
        $this->assertFalse($user->fresh()->activo);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $user->id,
            'actor_user_id' => $user->id,
            'accion' => 'usuario.desactivado_por_titular',
        ]);
    }

    public function test_last_active_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create(['rol' => \App\Enums\RolUsuario::Admin]);

        $response = $this
            ->actingAs($admin)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($admin->fresh()->activo);
        $this->assertDatabaseMissing('logs_auditoria', [
            'registro_id' => $admin->id,
            'accion' => 'usuario.desactivado_por_titular',
        ]);
    }

    public function test_correct_password_must_be_provided_to_deactivate_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}

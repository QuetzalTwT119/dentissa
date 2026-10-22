<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardVisualTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_the_branded_dashboard_and_its_main_actions(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel de administración')
            ->assertSee('Accesos rápidos')
            ->assertSee('Agendar cita')
            ->assertSee('Pacientes')
            ->assertSee('Gestión')
            ->assertSee('logo-rosa-transparente.png');
    }
}

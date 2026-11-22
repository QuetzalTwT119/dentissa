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
            // Marca global (22/11/2026): la misma firma que la portada.
            ->assertSee('data-marca', false)
            ->assertSee('Dra. Melissa López');
    }

    public function test_assistant_dashboard_speaks_to_the_staff_not_to_patients(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Panel de asistencia')
            ->assertSee('Tu espacio de trabajo')
            ->assertSee('Ver agenda')
            ->assertDontSee('Portal de Paciente')
            ->assertDontSee('Ver mis citas');
    }
}

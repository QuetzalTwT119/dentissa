<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_filtrar_eventos_y_ver_metadatos_de_cambio(): void
    {
        $admin = User::factory()->create([
            'nombre' => 'Melissa',
            'apellido' => 'Admin',
            'rol' => RolUsuario::SuperAdmin,
        ]);
        $asistente = User::factory()->create([
            'nombre' => 'Ana',
            'apellido' => 'Asistente',
            'rol' => RolUsuario::Asistente,
        ]);

        LogAuditoria::create([
            'id_usuario' => $admin->id,
            'accion' => 'cita.confirmada',
            'tabla' => 'citas',
            'registro_id' => 27,
            'datos_anteriores' => ['estado' => 'agendada'],
            'datos_nuevos' => ['estado' => 'confirmada'],
            'ip' => '192.0.2.10',
            'user_agent' => 'Agente de prueba privado',
            'fecha' => now()->subDay(),
        ]);

        LogAuditoria::create([
            'id_usuario' => $asistente->id,
            'accion' => 'cita.cancelada',
            'tabla' => 'citas',
            'registro_id' => 28,
            'datos_anteriores' => ['estado' => 'agendada'],
            'datos_nuevos' => ['estado' => 'cancelada'],
            'fecha' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index', [
                'usuario_id' => $admin->id,
                'accion' => 'cita.confirmada',
                'desde' => now()->subDays(2)->toDateString(),
                'hasta' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Melissa Admin')
            ->assertSee('Cita confirmada')
            ->assertSee('Cita')
            ->assertSee('#27')
            ->assertSee('Ver cambios')
            ->assertSee('agendada')
            ->assertSee('confirmada')
            ->assertDontSee('#28')
            ->assertDontSee('192.0.2.10')
            ->assertDontSee('Agente de prueba privado');
    }

    public function test_el_filtro_de_accion_solo_ofrece_eventos_visibles_en_la_bitacora(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

        LogAuditoria::create([
            'id_usuario' => $admin->id,
            'actor_user_id' => $admin->id,
            'actor_nombre' => 'Administradora',
            'actor_rol' => 'admin',
            'accion' => 'cita.confirmada',
            'tabla' => 'citas',
            'registro_id' => 41,
            'fecha' => now(),
        ]);

        LogAuditoria::create([
            'id_usuario' => null,
            'actor_user_id' => null,
            'actor_nombre' => 'Paciente del portal',
            'actor_rol' => 'paciente',
            'accion' => 'portal.solicitud_creada',
            'tabla' => 'citas',
            'registro_id' => 42,
            'fecha' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('value="cita.confirmada"', false)
            ->assertDontSee('value="portal.solicitud_creada"', false)
            ->assertDontSee('Paciente del portal');
    }

    public function test_asistente_no_puede_consultar_la_auditoria(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->get(route('admin.auditoria.index'))
            ->assertForbidden();
    }

    public function test_visitante_es_enviado_al_login_antes_de_consultar_auditoria(): void
    {
        $this->get(route('admin.auditoria.index'))
            ->assertRedirect(route('login'));
    }

    public function test_eventos_sin_usuario_del_personal_no_aparecen_en_la_bitacora(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

        foreach (['publico' => 31, 'telegram' => 32, 'interna' => 33] as $canal => $idCita) {
            LogAuditoria::create([
                'id_usuario' => null,
                'accion' => 'cita.creada',
                'tabla' => 'citas',
                'registro_id' => $idCita,
                'datos_nuevos' => ['canal' => $canal],
                'fecha' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            // El conteo siempre existe (el buscador sin recarga lo muestra u oculta),
            // pero sin eventos visibles va oculto y se muestra el vacío.
            ->assertSee('text-muted hidden" data-filtro-conteo', false)
            ->assertDontSee('Solicitud desde portal público')
            ->assertDontSee('Solicitud por Telegram')
            ->assertDontSee('Cita #31')
            ->assertDontSee('Cita #32')
            ->assertDontSee('Cita #33');
    }
}

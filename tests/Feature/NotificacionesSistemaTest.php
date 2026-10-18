<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Events\NotificacionSistemaCreada;
use App\Models\Cita;
use App\Models\NotificacionSistema;
use App\Models\User;
use App\Services\NotificacionInternaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificacionesSistemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_cambio_de_cita_avisa_a_los_otras_cuentas_activas_del_personal(): void
    {
        Event::fake([NotificacionSistemaCreada::class]);

        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        User::factory()->create(['rol' => RolUsuario::Asistente, 'activo' => false]);

        app(NotificacionInternaService::class)->cambioCita(
            Cita::make(['fecha_hora' => now()]),
            'confirmada',
            $admin->id,
        );

        $this->assertDatabaseCount('notificaciones_sistema', 1);
        $this->assertDatabaseHas('notificaciones_sistema', [
            'user_id' => $asistente->id,
            'evento' => 'confirmada',
            'titulo' => 'Cita confirmada',
        ]);
        $this->assertDatabaseMissing('notificaciones_sistema', ['user_id' => $admin->id]);
        Event::assertDispatched(NotificacionSistemaCreada::class, fn ($event) => $event->broadcastOn()[0]->name === 'private-users.'.$asistente->id);
    }

    public function test_admin_y_asistente_pueden_ver_y_marcar_sus_notificaciones(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $notificacion = NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'agendada',
            'titulo' => 'Nueva cita registrada',
            'mensaje' => 'Se registró una cita para mañana.',
            'url' => route('citas.index'),
        ]);

        $this->actingAs($admin)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('Nueva cita registrada')
            ->assertSee('Marcar todas como leídas');

        $this->patch(route('notificaciones.leida', $notificacion))
            ->assertRedirect();

        $this->assertDatabaseHas('notificaciones_sistema', [
            'id' => $notificacion->id,
            'user_id' => $admin->id,
        ]);
        $this->assertNotNull($notificacion->fresh()->leida_en);
    }

    public function test_la_campana_muestra_vista_previa_rapida_sin_exponer_avisos_de_otras_cuentas(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'agendada',
            'titulo' => 'Vista previa propia',
            'mensaje' => 'Detalle breve de la cita.',
            'url' => route('citas.index'),
        ]);
        NotificacionSistema::create([
            'user_id' => $asistente->id,
            'evento' => 'agendada',
            'titulo' => 'Aviso privado ajeno',
            'mensaje' => 'Este aviso no debe aparecer.',
        ]);

        $this->actingAs($admin)
            ->get(route('citas.index'))
            ->assertOk()
            ->assertSee('data-notification-preview-trigger', false)
            ->assertSee('data-notification-preview-panel', false)
            ->assertSee('Vista previa propia')
            ->assertDontSee('Aviso privado ajeno')
            ->assertSee('Ver todas las notificaciones');
    }

    public function test_la_bandeja_filtra_notificaciones_pendientes_y_leidas_y_permite_restaurar_una(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $pendiente = NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'agendada',
            'titulo' => 'Aviso pendiente',
            'mensaje' => 'Una cita requiere atención.',
        ]);
        $leida = NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'confirmada',
            'titulo' => 'Aviso leído',
            'mensaje' => 'La cita fue confirmada.',
            'leida_en' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('notificaciones.index', ['estado' => 'pendientes']))
            ->assertOk()
            ->assertSee('Aviso pendiente')
            ->assertDontSee('<h3 class="font-semibold text-gray-900">Aviso leído</h3>', false)
            ->assertSee('Pendientes');

        $this->actingAs($admin)
            ->get(route('notificaciones.index', ['estado' => 'leidas']))
            ->assertOk()
            ->assertSee('Aviso leído')
            ->assertDontSee('<h3 class="font-semibold text-gray-900">Aviso pendiente</h3>', false);

        $this->patch(route('notificaciones.pendiente', $leida))
            ->assertRedirect();

        $this->assertNull($leida->fresh()->leida_en);
        $this->assertNull($pendiente->fresh()->leida_en);
    }

    public function test_usuario_autenticado_consulta_solo_sus_actualizaciones_en_vivo(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $propia = NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'prueba',
            'titulo' => 'Aviso de prueba',
            'mensaje' => 'Debe aparecer en vivo.',
        ]);
        NotificacionSistema::create([
            'user_id' => $asistente->id,
            'evento' => 'prueba',
            'titulo' => 'Aviso privado',
            'mensaje' => 'No debe filtrarse a otra cuenta.',
        ]);

        $this->actingAs($admin)
            ->getJson(route('notificaciones.actualizaciones', ['desde_id' => 0]))
            ->assertOk()
            ->assertJsonPath('no_leidas', 1)
            ->assertJsonPath('ultima_id', $propia->id)
            ->assertJsonCount(1, 'notificaciones')
            ->assertJsonPath('notificaciones.0.titulo', 'Aviso de prueba');
    }

    public function test_el_canal_en_vivo_solo_autoriza_a_la_cuenta_propietaria(): void
    {
        config(['broadcasting.default' => 'reverb']);

        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($admin)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '123.456',
                'channel_name' => 'private-users.'.$admin->id,
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '123.456',
                'channel_name' => 'private-users.'.$asistente->id,
            ])
            ->assertForbidden();
    }

    public function test_una_cuenta_no_puede_marcar_la_notificacion_de_otra(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $notificacion = NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'agendada',
            'titulo' => 'Nueva cita registrada',
            'mensaje' => 'Se registró una cita.',
        ]);

        $this->actingAs($asistente)
            ->patch(route('notificaciones.leida', $notificacion))
            ->assertNotFound();

        $this->actingAs($asistente)
            ->patch(route('notificaciones.pendiente', $notificacion))
            ->assertNotFound();

        $this->assertNull($notificacion->fresh()->leida_en);
    }
}

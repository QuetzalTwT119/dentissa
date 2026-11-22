<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Events\NotificacionSistemaCreada;
use App\Models\Cita;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
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

        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        // 22/11/2026: los avisos van a quien tiene el módulo de notificaciones (doctor), ya no a la asistente.
        $asistente = User::factory()->create(['rol' => RolUsuario::Doctor]);
        User::factory()->create(['rol' => RolUsuario::Doctor, 'activo' => false]);
        User::factory()->create(['rol' => RolUsuario::Asistente]);

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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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

    public function test_abrir_un_aviso_lo_puede_marcar_como_leido_y_devuelve_el_total_actualizado(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $notificacion = NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'agendada',
            'titulo' => 'Nueva cita registrada',
            'mensaje' => 'Se registró una cita.',
        ]);

        $this->actingAs($admin)
            ->patchJson(route('notificaciones.leida', $notificacion))
            ->assertOk()
            ->assertJsonPath('no_leidas', 0)
            ->assertJsonStructure(['leida_en']);

        $this->assertNotNull($notificacion->fresh()->leida_en);
    }

    public function test_la_campana_muestra_vista_previa_rapida_sin_exponer_avisos_de_otras_cuentas(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'agendada',
            'titulo' => 'Vista previa propia',
            'mensaje' => 'Detalle breve de la cita.',
            'url' => route('citas.index'),
        ]);
        NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'confirmada',
            'titulo' => 'Vista previa leída',
            'mensaje' => 'Debe conservarse en el historial corto.',
            'leida_en' => now(),
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
            ->assertSee('Vista previa leída')
            ->assertDontSee('Aviso privado ajeno')
            ->assertSee('Ver todas las notificaciones');
    }

    public function test_abrir_la_campana_marca_los_avisos_como_vistos_sin_cerrar_los_pendientes(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $notificacion = NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'agendada',
            'titulo' => 'Aviso para la campana',
            'mensaje' => 'Debe permanecer visible después de leerse.',
        ]);

        $this->actingAs($admin)
            ->patchJson(route('notificaciones.vistas'))
            ->assertOk()
            ->assertJsonPath('vistas', 1)
            ->assertJsonPath('no_vistas', 0);

        $this->assertNotNull($notificacion->fresh()->vista_en);
        $this->assertNull($notificacion->fresh()->leida_en);
        $this->actingAs($admin)
            ->get(route('citas.index'))
            ->assertOk()
            ->assertSee('Aviso para la campana')
            ->assertSee('bg-brand-600', false);
    }

    public function test_el_centro_ordena_los_avisos_por_dia_y_muestra_su_tipo(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);

        NotificacionSistema::create([
            'user_id' => $admin->id,
            'evento' => 'cancelada',
            'titulo' => 'Cita cancelada',
            'mensaje' => 'La cita requiere seguimiento.',
        ]);

        $this->actingAs($admin)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee('Hoy')
            ->assertSee('Cancelación')
            ->assertSee('border-rose-200', false);
    }

    public function test_la_bandeja_filtra_notificaciones_pendientes_y_leidas_y_permite_restaurar_una(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
            ->assertJsonPath('no_vistas', 1)
            ->assertJsonPath('ultima_id', $propia->id)
            ->assertJsonCount(1, 'notificaciones')
            ->assertJsonPath('notificaciones.0.titulo', 'Aviso de prueba')
            ->assertJsonPath('notificaciones.0.leida_url', route('notificaciones.leida', $propia, false));
    }

    public function test_el_canal_en_vivo_solo_autoriza_a_la_cuenta_propietaria(): void
    {
        // Credenciales ficticias: la prueba no debe depender del .env local.
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'clave-prueba',
            'broadcasting.connections.reverb.secret' => 'secreto-prueba',
            'broadcasting.connections.reverb.app_id' => 'app-prueba',
        ]);
        // Los canales se registraron al arrancar en el broadcaster "null";
        // se vuelven a registrar en el broadcaster Reverb recién activado.
        require base_path('routes/channels.php');

        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
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
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Doctor]);
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

    public function test_el_sondeo_de_notificaciones_no_consume_el_mensaje_de_la_accion_previa(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $paciente = Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'Ficticio',
            'telefono' => '5512345678',
            'email' => 'paciente@ejemplo.test',
        ]);
        $servicio = Servicio::create(['nombre' => 'Consulta de prueba', 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Limpieza de prueba', 'duracion_estimada' => 30, 'categoria' => 'Preventiva', 'activo' => true]);
        $servicio->tratamientos()->attach($tratamiento);
        $cita = Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_hora' => now()->addDays(2)->setTime(10, 0),
            'duracion_estimada' => 30,
            'estado' => EstadoCita::Agendada,
        ]);

        $this->actingAs($admin)
            ->patch(route('citas.confirm', $cita))
            ->assertSessionHas('success', 'Cita confirmada correctamente.');

        // El sondeo en segundo plano llega antes que la página a la que se redirige.
        $this->getJson(route('notificaciones.actualizaciones', ['desde_id' => 0]))->assertOk();

        $this->get(route('citas.index'))->assertSee('Cita confirmada correctamente.');

        // Una vez mostrado, el mensaje no reaparece en la siguiente navegación.
        $this->getJson(route('notificaciones.actualizaciones', ['desde_id' => 0]))->assertOk();
        $this->get(route('citas.index'))->assertDontSee('Cita confirmada correctamente.');
    }
}

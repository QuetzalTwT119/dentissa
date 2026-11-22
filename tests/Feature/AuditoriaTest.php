<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_the_activity_log(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.auditoria.index'))
            ->assertOk()
            ->assertSee('Control de actividades')
            ->assertSee('Solo lectura. Se registran acciones de cuentas del personal');
    }

    public function test_assistant_cannot_see_the_activity_log(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)
            ->get(route('admin.auditoria.index'))
            ->assertForbidden();
    }

    public function test_authenticated_changes_are_registered_without_sensitive_values(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin);
        $equipo = User::factory()->create(['rol' => RolUsuario::Asistente]);
        // El evento sin detalle se escribe al terminar la petición (BitacoraPendiente).
        app(\App\Support\BitacoraPendiente::class)->escribir();

        $registro = LogAuditoria::query()
            ->where('id_usuario', $admin->id)
            ->where('tabla', 'users')
            ->where('registro_id', $equipo->id)
            ->first();

        $this->assertNotNull($registro);
        $this->assertSame('creado', $registro->accion);
        $this->assertArrayHasKey('campos_modificados', $registro->datos_nuevos);
        $this->assertNotContains($equipo->email, $registro->datos_nuevos['campos_modificados']);
        // Ahora también guarda quién y con qué rol (antes salía "Personal").
        $this->assertSame('admin', $registro->actor_rol);
    }

    /**
     * QA (22/11/2026): cada cambio quedaba dos veces (con detalle y genérico) y el
     * de detalle tomaba la hora de la base de datos (6 horas adelantado).
     */
    public function test_cada_cambio_queda_una_vez_con_la_hora_correcta_y_la_direccion_nueva(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        \Illuminate\Support\Carbon::setTestNow('2026-11-22 03:09:34');

        $oferta = \App\Models\Oferta::create(['titulo' => 'Limpieza express', 'descripcion' => 'Para pacientes nuevos y de siempre.', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 10,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'estado' => 'activa']);
        $this->actingAs($admin)->delete(route('admin.ofertas.destroy', $oferta))->assertSessionHas('success');

        $eventos = LogAuditoria::where('tabla', 'ofertas')->where('registro_id', $oferta->id)->get();
        $this->assertSame(['oferta.eliminada'], $eventos->pluck('accion')->all());
        $this->assertSame('03:09', $eventos->first()->fecha->format('H:i'));
        \Illuminate\Support\Carbon::setTestNow();

        // La dirección se llama como la pantalla; la de antes redirige con sus filtros.
        $this->assertStringEndsWith('/admin/control-de-actividades', route('admin.auditoria.index'));
        $this->actingAs($admin)->get('/admin/auditoria?accion=oferta.eliminada')
            ->assertRedirect(route('admin.auditoria.index', ['accion' => 'oferta.eliminada']));
        $this->actingAs($admin)->get(route('admin.auditoria.index'))->assertOk()->assertSee('Oferta eliminada');
    }
}

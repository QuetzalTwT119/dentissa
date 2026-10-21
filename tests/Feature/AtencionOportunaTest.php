<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\ListaEspera;
use App\Models\Servicio;
use App\Models\SolicitudUrgencia;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtencionOportunaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pantalla_de_urgencias_se_adapta_a_movil_y_escritorio(): void
    {
        $this->get(route('atencion.urgencia'))
            ->assertOk()
            ->assertSee('aspect-[2/1]')
            ->assertSee('lg:grid-cols-[minmax(380px,0.95fr)_minmax(0,1.05fr)]')
            ->assertSee('lg:gap-6')
            ->assertSee('max-w-screen-2xl')
            ->assertDontSee('lg:pr-48')
            ->assertDontSee('lg:pr-40')
            ->assertSee('pb-8 sm:px-6 sm:py-10 lg:pb-10')
            ->assertSee('space-y-3 sm:space-y-4')
            ->assertSee('min-h-11 w-full')
            ->assertSee('sm:min-w-52')
            ->assertSee('inline-flex items-center gap-2 rounded-full border border-brand-100 bg-white')
            ->assertSee('h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600')
            ->assertSee('whitespace-nowrap leading-none')
            ->assertSee('Enviar solicitud');
    }

    public function test_un_paciente_puede_enviar_una_solicitud_urgente_sin_que_se_agende_automaticamente(): void
    {
        $this->post(route('atencion.urgencia.store'), [
            'nombre' => 'Ana Prueba',
            'telefono' => '5512345678',
            'email' => 'ana@example.test',
            'motivo' => 'Tengo dolor intenso desde ayer por la noche.',
            'senales_reportadas' => ['dolor', 'inflamacion'],
        ])->assertRedirect(route('atencion.urgencia'));

        $this->assertDatabaseHas('solicitudes_urgencia', [
            'nombre' => 'Ana Prueba',
            'estado' => 'pendiente',
        ]);
        $this->assertDatabaseCount('citas', 0);
    }

    public function test_un_paciente_puede_unirse_a_la_lista_de_espera_y_el_admin_la_ve(): void
    {
        $servicio = Servicio::create(['nombre' => 'Preventiva', 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Limpieza', 'duracion_estimada' => 30, 'categoria' => 'Preventiva', 'activo' => true]);
        $servicio->tratamientos()->attach($tratamiento);

        $this->post(route('atencion.lista-espera.store'), [
            'nombre' => 'Luis Prueba',
            'telefono' => '5587654321',
            'id_servicio' => $servicio->id,
            'id_tratamiento' => $tratamiento->id,
            'fecha_preferida' => now()->addDay()->toDateString(),
            'franja_horaria' => 'manana',
        ])->assertRedirect(route('atencion.lista-espera'));

        $solicitud = ListaEspera::firstOrFail();
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.atencion-oportuna.index'))
            ->assertOk()
            ->assertSee('Luis Prueba')
            ->assertSee('Limpieza');

        $this->actingAs($admin)
            ->patch(route('admin.atencion-oportuna.espera.contactar', $solicitud))
            ->assertRedirect();

        $this->assertDatabaseHas('lista_espera', ['id' => $solicitud->id, 'estado' => 'contactada']);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $admin->id,
            'accion' => 'lista_espera.contactada',
            'tabla' => 'lista_espera',
            'registro_id' => $solicitud->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.atencion-oportuna.index', ['historial_estado' => 'contactada']))
            ->assertOk()
            ->assertSee('Historial de seguimiento')
            ->assertSee('Luis Prueba');
    }

    public function test_la_lista_de_espera_rechaza_un_tratamiento_que_no_pertenece_al_servicio_elegido(): void
    {
        $servicioElegido = Servicio::create(['nombre' => 'Preventiva', 'activo' => true]);
        $otroServicio = Servicio::create(['nombre' => 'Estética', 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Blanqueamiento', 'duracion_estimada' => 45, 'categoria' => 'Estética', 'activo' => true]);
        $otroServicio->tratamientos()->attach($tratamiento);

        $this->post(route('atencion.lista-espera.store'), [
            'nombre' => 'Luis Prueba',
            'telefono' => '5587654321',
            'id_servicio' => $servicioElegido->id,
            'id_tratamiento' => $tratamiento->id,
            'franja_horaria' => 'manana',
        ])->assertSessionHasErrors('id_tratamiento');

        $this->assertDatabaseCount('lista_espera', 0);
    }

    public function test_el_admin_puede_marcar_una_urgencia_como_contactada(): void
    {
        $urgencia = SolicitudUrgencia::create([
            'nombre' => 'Eva Prueba',
            'telefono' => '5511111111',
            'motivo' => 'Tengo un golpe dental y dolor desde hace una hora.',
        ]);
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->patch(route('admin.atencion-oportuna.urgencias.contactar', $urgencia))
            ->assertRedirect();

        $this->assertDatabaseHas('solicitudes_urgencia', ['id' => $urgencia->id, 'estado' => 'contactada']);
        $this->assertDatabaseHas('logs_auditoria', [
            'id_usuario' => $admin->id,
            'accion' => 'urgencia.contactada',
            'tabla' => 'solicitudes_urgencia',
            'registro_id' => $urgencia->id,
        ]);
    }

    public function test_una_solicitud_ya_contactada_no_puede_registrarse_como_contactada_por_segunda_vez(): void
    {
        $urgencia = SolicitudUrgencia::create([
            'nombre' => 'Eva Prueba',
            'telefono' => '5511111111',
            'motivo' => 'Tengo un golpe dental y dolor desde hace una hora.',
            'estado' => 'contactada',
            'contactado_por' => User::factory()->create(['rol' => RolUsuario::Admin])->id,
            'fecha_contacto' => now()->subHour(),
        ]);
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->patch(route('admin.atencion-oportuna.urgencias.contactar', $urgencia))
            ->assertSessionHasErrors('estado');

        $this->assertSame('contactada', $urgencia->fresh()->estado);
        $this->assertDatabaseCount('logs_auditoria', 0);
    }
}

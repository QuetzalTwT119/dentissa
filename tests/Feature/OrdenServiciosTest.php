<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El orden del carrusel se acomoda con flechas ↑ ↓ en la lista de servicios,
 * no escribiendo un número en el formulario (decisión del 20/11/2026).
 */
class OrdenServiciosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => RolUsuario::Admin]);
    }

    private function nombresEnOrden(): array
    {
        return Servicio::orderBy('orden')->orderBy('id')->pluck('nombre')->all();
    }

    public function test_las_flechas_suben_y_bajan_un_lugar(): void
    {
        $general = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        Servicio::create(['nombre' => 'Odontología Estética', 'orden' => 2, 'activo' => true]);
        $ortodoncia = Servicio::create(['nombre' => 'Ortodoncia', 'orden' => 3, 'activo' => true]);

        $this->actingAs($this->admin)
            ->from(route('admin.servicios.index'))
            ->patch(route('admin.servicios.mover', [$ortodoncia, 'arriba']))
            ->assertRedirect(route('admin.servicios.index'))
            ->assertSessionHas('success');
        $this->assertSame(['Odontología General', 'Ortodoncia', 'Odontología Estética'], $this->nombresEnOrden());

        $this->actingAs($this->admin)->patch(route('admin.servicios.mover', [$general, 'abajo']));
        $this->assertSame(['Ortodoncia', 'Odontología General', 'Odontología Estética'], $this->nombresEnOrden());

        // La portada respeta el nuevo orden.
        $this->get('/')->assertSeeInOrder(['Ortodoncia', 'Odontología General', 'Odontología Estética']);
    }

    public function test_en_los_extremos_no_hace_nada_y_los_ordenes_repetidos_se_corrigen(): void
    {
        // Datos viejos con el mismo orden (0, 0, 0): mover igual debe funcionar.
        $a = Servicio::create(['nombre' => 'A', 'orden' => 0, 'activo' => true]);
        Servicio::create(['nombre' => 'B', 'orden' => 0, 'activo' => true]);
        $c = Servicio::create(['nombre' => 'C', 'orden' => 0, 'activo' => true]);

        $this->actingAs($this->admin)->patch(route('admin.servicios.mover', [$a, 'arriba']));
        $this->actingAs($this->admin)->patch(route('admin.servicios.mover', [$c, 'abajo']));
        $this->assertSame(['A', 'B', 'C'], $this->nombresEnOrden());

        $this->actingAs($this->admin)->patch(route('admin.servicios.mover', [$c, 'arriba']));
        $this->assertSame(['A', 'C', 'B'], $this->nombresEnOrden());
        $this->assertSame([1, 2, 3], Servicio::orderBy('orden')->pluck('orden')->all());
    }

    public function test_la_lista_muestra_posicion_y_flechas_y_los_formularios_ya_no_piden_orden(): void
    {
        $primero = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        Servicio::create(['nombre' => 'Odontología Estética', 'orden' => 2, 'activo' => true]);

        $this->actingAs($this->admin)
            ->get(route('admin.servicios.index'))
            ->assertOk()
            ->assertSee('Posición en el carrusel')
            ->assertSee('data-posicion-servicio="1"', false)
            ->assertSee('Subir «Odontología Estética» un lugar')
            ->assertSee('Bajar «Odontología General» un lugar');

        $this->actingAs($this->admin)->get(route('admin.servicios.edit', $primero))->assertDontSee('name="orden"', false);
        $this->actingAs($this->admin)->get(route('admin.servicios.create'))->assertDontSee('name="orden"', false);
    }

    public function test_un_servicio_nuevo_se_agrega_al_final(): void
    {
        Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        Servicio::create(['nombre' => 'Odontología Estética', 'orden' => 4, 'activo' => true]);

        $this->actingAs($this->admin)
            ->post(route('admin.servicios.store'), ['nombre' => 'Ortodoncia', 'activo' => '1'])
            ->assertRedirect();

        $this->assertSame(5, Servicio::where('nombre', 'Ortodoncia')->value('orden'));
    }

    public function test_la_asistente_no_puede_reordenar(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($asistente)->patch(route('admin.servicios.mover', [$servicio, 'abajo']))->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 22/11/2026 (encontrado por el usuario): "Nuevo servicio" era el formulario
 * viejo (Icono, casilla "Servicio activo", sin tratamientos ni vista previa) y
 * "Editar" el nuevo. Además el mismo dato se llamaba "Activo" en la lista y
 * "Disponible para agendar" en Editar. Ahora es un solo formulario y un solo nombre.
 */
class FormularioServicioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => RolUsuario::Admin]);
    }

    public function test_nuevo_y_editar_usan_el_mismo_formulario(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'activo' => true, 'precio_referencia' => 500]);

        foreach ([route('admin.servicios.create'), route('admin.servicios.edit', $servicio)] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk()
                ->assertSee('id="form-servicio"', false)
                ->assertSee('Datos del servicio')
                ->assertSee('Tratamientos que se pueden agendar')
                ->assertSee('Limpieza Dental')
                ->assertSee('data-vista-previa-servicio', false)
                ->assertSee('data-ayuda-disponible', false)
                ->assertSee('se muestra en la portada y se puede agendar')
                ->assertDontSee('name="icono"', false)
                ->assertDontSee('Disponible para agendar')
                ->assertDontSee('Servicio activo');
        }

        // Nuevo: disponible por omisión y con su propio botón.
        $this->actingAs($this->admin)->get(route('admin.servicios.create'))
            ->assertSee('Nuevo servicio')
            ->assertSee('Crear servicio')
            ->assertSee('role="switch" aria-describedby="ayuda-disponible" class="peer sr-only" checked', false);
    }

    public function test_al_crear_ya_se_guardan_los_tratamientos(): void
    {
        $limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'activo' => true, 'precio_referencia' => 500]);

        $this->actingAs($this->admin)
            ->post(route('admin.servicios.store'), ['nombre' => 'Ortodoncia', 'activo' => '1', 'tratamientos_enviados' => '1', 'tratamiento_ids' => [$limpieza->id]])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Servicio «Ortodoncia» creado.');

        $this->assertSame([$limpieza->id], Servicio::where('nombre', 'Ortodoncia')->firstOrFail()->tratamientos()->pluck('tratamientos.id')->all());

        // Sin tratamientos se avisa qué verá el paciente.
        $this->actingAs($this->admin)
            ->post(route('admin.servicios.store'), ['nombre' => 'Endodoncia', 'activo' => '1', 'tratamientos_enviados' => '1'])
            ->assertSessionHas('success', 'Servicio «Endodoncia» creado. Sin tratamientos, la portada lo muestra como "Agenda por teléfono".');
    }

    public function test_la_lista_y_el_filtro_usan_el_mismo_nombre_que_el_formulario(): void
    {
        Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        Servicio::create(['nombre' => 'Ortodoncia', 'orden' => 2, 'activo' => false]);

        $html = $this->actingAs($this->admin)->get(route('admin.servicios.index'))->assertOk()->getContent();

        $this->assertStringContainsString('>Disponible</span>', $html);
        $this->assertStringContainsString('>No disponible</span>', $html);
        $this->assertStringContainsString('No disponibles', $html);
        $this->assertStringContainsString('ya no se podrá agendar', $html);
        $this->assertStringNotContainsString('>Activo</span>', $html);
    }
}

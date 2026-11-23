<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Oferta;
use App\Models\PreguntaFrecuente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo H-15 (20/11/2026): crear, guardar y eliminar no avisaban nada. El
 * layout del panel no pintaba los mensajes de sesión (solo Citas y
 * Notificaciones lo hacían) y 36 acciones ni siquiera los enviaban.
 */
class AvisosAccionesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    public function test_crear_guardar_y_eliminar_un_tratamiento_avisan_que_paso(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tratamientos.store'), ['nombre' => 'Valoración', 'duracion_estimada' => 30, 'precio_referencia' => 300])
            ->assertSessionHas('success', 'Tratamiento «Valoración» creado.');

        $tratamiento = Tratamiento::where('nombre', 'Valoración')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.tratamientos.update', $tratamiento), ['nombre' => 'Valoración', 'duracion_estimada' => 30, 'precio_referencia' => 350])
            ->assertSessionHas('success', 'Tratamiento «Valoración» guardado.');

        $this->actingAs($this->admin)
            ->delete(route('admin.tratamientos.destroy', $tratamiento))
            ->assertSessionHas('success', 'Tratamiento «Valoración» eliminado.');
    }

    public function test_el_panel_muestra_el_aviso_en_cualquier_pantalla(): void
    {
        $servicio = Servicio::create(['nombre' => 'Ortodoncia', 'orden' => 1, 'activo' => true]);

        // Seguir la redirección: el aviso se pinta en la lista de servicios (que antes no lo hacía).
        $this->actingAs($this->admin)
            ->followingRedirects()
            ->delete(route('admin.servicios.destroy', $servicio))
            ->assertOk()
            ->assertSee('data-aviso-sesion="success"', false)
            ->assertSee('Servicio «Ortodoncia» eliminado.');
    }

    public function test_los_errores_tambien_se_avisan_y_no_se_duplican(): void
    {
        $tratamiento = Tratamiento::create(['nombre' => 'Carillas de Porcelana', 'duracion_estimada' => 120, 'activo' => false]);

        $html = $this->actingAs($this->admin)
            ->from(route('admin.tratamientos.index'))
            ->followingRedirects()
            ->patch(route('admin.tratamientos.estado', $tratamiento))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-aviso-sesion="error"', $html);
        $this->assertSame(1, substr_count($html, 'Agrega el precio de «Carillas de Porcelana» antes de activarlo.'), 'El aviso no debe salir dos veces.');
    }

    public function test_otras_secciones_tambien_avisan(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.preguntas-frecuentes.store'), ['pregunta' => '¿Atienden niños?', 'respuesta' => 'Sí, desde los 3 años.', 'orden' => 1])
            ->assertSessionHas('success', 'Pregunta frecuente creada.');

        $oferta = Oferta::create([
            'titulo' => 'Limpieza de temporada', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 10,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'estado' => 'activa',
        ]);
        $this->actingAs($this->admin)
            ->delete(route('admin.ofertas.destroy', $oferta))
            ->assertSessionHas('success', 'Oferta «Limpieza de temporada» eliminada.');

        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente, 'nombre' => 'Ana', 'apellido' => 'López']);
        $this->actingAs($this->admin)
            ->patch(route('admin.usuarios.activo.toggle', $asistente))
            ->assertSessionHas('success', 'Ana López está desactivado: ya no puede iniciar sesión.');

        $this->assertNotNull(PreguntaFrecuente::first());
    }
}

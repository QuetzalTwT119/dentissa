<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Enums\TipoImagen;
use App\Models\Imagen;
use App\Models\Oferta;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hallazgo H-10 (20/11/2026): una casilla desmarcada no se envía en el
 * formulario, y el servidor lo interpretaba como "sin cambio" (o como "sí").
 * Desactivar "Blanqueamiento Dental" no funcionaba. Cada prueba envía el
 * formulario SIN la casilla, como lo hace el navegador.
 */
class CasillasDesmarcadasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => RolUsuario::Admin]);
    }

    /** Desde H-12 el estado del tratamiento se cambia desde la lista, no con una casilla. */
    public function test_activar_y_desactivar_un_tratamiento_desde_la_lista(): void
    {
        $tratamiento = Tratamiento::create([
            'nombre' => 'Blanqueamiento Dental', 'duracion_estimada' => 90, 'sesiones_requeridas' => 1,
            'requiere_autorizacion' => true, 'activo' => true, 'precio_referencia' => 3500,
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.tratamientos.index'))
            ->patch(route('admin.tratamientos.estado', $tratamiento))
            ->assertRedirect(route('admin.tratamientos.index'))
            ->assertSessionHas('success');
        $this->assertFalse($tratamiento->fresh()->activo);

        $this->actingAs($this->admin)->patch(route('admin.tratamientos.estado', $tratamiento));
        $this->assertTrue($tratamiento->fresh()->activo);

        // Guardar el formulario (que ya no trae casillas) no toca el estado ni la autorización.
        $this->actingAs($this->admin)
            ->put(route('admin.tratamientos.update', $tratamiento), [
                'nombre' => 'Blanqueamiento Dental', 'duracion_estimada' => 90, 'precio_referencia' => 3500,
            ])
            ->assertSessionHasNoErrors();
        $tratamiento->refresh();
        $this->assertTrue($tratamiento->activo);
        $this->assertTrue((bool) $tratamiento->requiere_autorizacion);
    }

    public function test_un_tratamiento_nuevo_nace_activo_y_la_asistente_no_cambia_estados(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.tratamientos.store'), ['nombre' => 'Valoración', 'duracion_estimada' => 30, 'precio_referencia' => 300])
            ->assertSessionHasNoErrors();
        $tratamiento = Tratamiento::where('nombre', 'Valoración')->firstOrFail();
        $this->assertTrue($tratamiento->activo);

        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $this->actingAs($asistente)->patch(route('admin.tratamientos.estado', $tratamiento))->assertForbidden();
        $this->assertTrue($tratamiento->fresh()->activo);
    }

    public function test_el_formulario_de_tratamiento_solo_pide_lo_que_se_usa(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología Estética', 'orden' => 1, 'activo' => true]);
        $tratamiento = Tratamiento::create(['nombre' => 'Blanqueamiento Dental', 'duracion_estimada' => 90, 'sesiones_requeridas' => 1, 'activo' => true]);
        $servicio->tratamientos()->attach($tratamiento->id, ['orden' => 1]);

        foreach ([route('admin.tratamientos.create'), route('admin.tratamientos.edit', $tratamiento)] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk()
                ->assertDontSee('name="requiere_autorizacion"', false)
                ->assertDontSee('name="activo"', false)
                ->assertDontSee('name="imagen"', false)
                ->assertDontSee('name="sesiones_requeridas"', false)
                ->assertDontSee('name="descripcion"', false)
                ->assertDontSee('name="categoria"', false)
                ->assertSee('Puedes guardar sin precio, pero no se publicará')
                ->assertSee('data-vista-previa-tratamiento', false)
                ->assertSee('Así lo verá el paciente');
        }

        // Editar dice dónde aparece (se cambia en el servicio), sin duplicar la edición.
        $this->actingAs($this->admin)->get(route('admin.tratamientos.edit', $tratamiento))
            ->assertSee('Aparece en')
            ->assertSee(route('admin.servicios.edit', $servicio), false)
            ->assertDontSee('name="servicio_ids[]"', false);

        // La lista permite cambiar el estado con un clic.
        $this->actingAs($this->admin)->get(route('admin.tratamientos.index'))
            ->assertSee('data-alternar-estado', false)
            ->assertSee('role="switch" aria-checked="true"', false)
            ->assertSee(route('admin.tratamientos.estado', $tratamiento), false);
    }

    public function test_sin_precio_no_se_publica(): void
    {
        $sinPrecio = Tratamiento::create(['nombre' => 'Carillas de Porcelana', 'duracion_estimada' => 120, 'activo' => false]);

        // El interruptor no activa un tratamiento sin precio.
        $this->actingAs($this->admin)
            ->from(route('admin.tratamientos.index'))
            ->patch(route('admin.tratamientos.estado', $sinPrecio))
            ->assertRedirect(route('admin.tratamientos.index'))
            ->assertSessionHas('error', 'Agrega el precio de «Carillas de Porcelana» antes de activarlo.');
        $this->assertFalse($sinPrecio->fresh()->activo);

        // En la lista sale bloqueado, con el camino para resolverlo.
        $this->actingAs($this->admin)->get(route('admin.tratamientos.index'))
            ->assertSee('data-falta-precio', false)
            ->assertSee('Falta precio');

        // Se puede GUARDAR sin precio (sigue sin publicarse).
        $this->actingAs($this->admin)
            ->put(route('admin.tratamientos.update', $sinPrecio), ['nombre' => 'Carillas de Porcelana', 'duracion_estimada' => 120])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Tratamiento «Carillas de Porcelana» guardado sin precio: no se publica hasta que le pongas precio.');
        $this->assertFalse($sinPrecio->fresh()->activo);

        // Crear sin precio: se guarda inactivo y lo avisa.
        $this->actingAs($this->admin)
            ->post(route('admin.tratamientos.store'), ['nombre' => 'Guarda Oclusal', 'duracion_estimada' => 45])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Tratamiento «Guarda Oclusal» creado sin precio: no se publicará hasta que le pongas precio.');
        $this->assertFalse(Tratamiento::where('nombre', 'Guarda Oclusal')->value('activo'));

        // Con precio ya se puede activar.
        $this->actingAs($this->admin)->put(route('admin.tratamientos.update', $sinPrecio), ['nombre' => 'Carillas de Porcelana', 'duracion_estimada' => 120, 'precio_referencia' => 4000]);
        $this->actingAs($this->admin)->patch(route('admin.tratamientos.estado', $sinPrecio));
        $this->assertTrue($sinPrecio->fresh()->activo);
        $this->assertSame('$4,000', $sinPrecio->fresh()->precioFormateado());

        // Quitarle el precio a uno publicado lo despublica (activo ⇒ tiene precio).
        $this->actingAs($this->admin)->put(route('admin.tratamientos.update', $sinPrecio), ['nombre' => 'Carillas de Porcelana', 'duracion_estimada' => 120, 'precio_referencia' => '']);
        $this->assertFalse($sinPrecio->fresh()->activo);
    }

    public function test_el_paciente_ve_el_precio_al_agendar(): void
    {
        $estetica = Servicio::create(['nombre' => 'Odontología Estética', 'orden' => 1, 'activo' => true]);
        $estetica->tratamientos()->attach(
            Tratamiento::create(['nombre' => 'Blanqueamiento Dental', 'duracion_estimada' => 90, 'activo' => true, 'precio_referencia' => 3500])->id,
            ['orden' => 1],
        );

        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('data-precio-tratamiento', false)
            ->assertSee('formatoPrecio(tratamientoSeleccionado.precio_referencia)', false)
            ->assertSee('3500', false);
    }

    public function test_desactivar_pide_confirmacion_y_explica_la_consecuencia_activar_no(): void
    {
        $estetica = Servicio::create(['nombre' => 'Odontología Estética', 'orden' => 1, 'activo' => true]);
        $general = Servicio::create(['nombre' => 'Odontología General', 'orden' => 2, 'activo' => true]);
        $blanqueamiento = Tratamiento::create(['nombre' => 'Blanqueamiento Dental', 'duracion_estimada' => 90, 'activo' => true]);
        $limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);
        $valoracion = Tratamiento::create(['nombre' => 'Valoración', 'duracion_estimada' => 30, 'activo' => true]);
        $carillas = Tratamiento::create(['nombre' => 'Carillas de Porcelana', 'duracion_estimada' => 120, 'activo' => false]);
        $estetica->tratamientos()->attach([$blanqueamiento->id => ['orden' => 1], $carillas->id => ['orden' => 2]]);
        $general->tratamientos()->attach([$limpieza->id => ['orden' => 1], $valoracion->id => ['orden' => 2]]);

        $html = $this->actingAs($this->admin)->get(route('admin.tratamientos.index'))->assertOk()->getContent();

        // Ya no hay columna "Servicios" (la relación se ve en "Se ofrece en").
        $this->assertStringNotContainsString('>Servicios</th>', $html);

        // Último tratamiento activo de Estética: avisa que pasará a "por teléfono".
        $this->assertStringContainsString('data-confirmar-titulo="¿Desactivar «Blanqueamiento Dental»?"', $html);
        $this->assertStringContainsString('«Odontología Estética» se quedará sin tratamientos', $html);

        // General tiene otro activo: confirma, pero sin anunciar que se queda vacía.
        $this->assertStringContainsString('data-confirmar-titulo="¿Desactivar «Limpieza Dental»?"', $html);
        $this->assertStringNotContainsString('«Odontología General» se quedará', $html);

        // Activar no pide confirmación.
        $this->assertStringNotContainsString('¿Desactivar «Carillas de Porcelana»?', $html);
    }

    /** 21/11/2026: ya no hay casilla "Mostrar"; se oculta desde la lista. */
    public function test_desactivar_una_oferta(): void
    {
        $oferta = Oferta::create([
            'titulo' => 'Limpieza de temporada', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 10,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'estado' => 'activa',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.ofertas.ocultar', $oferta))
            ->assertSessionHas('success');

        $this->assertSame('inactiva', $oferta->fresh()->estado->value);
    }

    public function test_desactivar_una_foto_de_la_galeria(): void
    {
        $imagen = Imagen::create(['titulo' => 'Recepción', 'url' => 'galeria/recepcion.jpg', 'tipo' => TipoImagen::Galeria, 'orden' => 1, 'activa' => true]);

        // Antes: "activa ?? true" convertía la casilla desmarcada en "sí".
        $this->actingAs($this->admin)
            ->put(route('admin.galeria.update', $imagen), ['titulo' => 'Recepción', 'orden' => 1])
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $imagen->fresh()->activa);
    }

    /**
     * 22/11/2026: el formulario ya no tiene "Disponible". Un servicio nuevo nace
     * disponible y se oculta con el interruptor de la lista (antes una casilla
     * desmarcada lo creaba oculto).
     */
    public function test_un_servicio_nuevo_nace_disponible(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.servicios.store'), ['nombre' => 'Ortodoncia', 'activo' => '0'])
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) Servicio::where('nombre', 'Ortodoncia')->value('activo'));
    }
}

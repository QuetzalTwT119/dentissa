<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Editar servicio en un solo formulario: antes había dos (datos/foto y
 * tratamientos) y elegir una foto para luego pulsar "Guardar tratamientos"
 * la perdía sin avisar (hallazgo de la verificación manual del 20/11/2026).
 */
class ServicioEdicionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => RolUsuario::Admin]);
    }

    public function test_la_pantalla_muestra_la_foto_actual_y_un_solo_boton_de_guardar(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);

        $this->actingAs($this->admin)
            ->get(route('admin.servicios.edit', $servicio))
            ->assertOk()
            ->assertSee('data-vista-previa-servicio', false)
            ->assertSee($servicio->fotoPublica(), false)
            ->assertSee('Foto predeterminada de la clínica')
            ->assertSee('name="tratamientos_enviados"', false)
            ->assertSee('Guardar cambios')
            ->assertDontSee('Guardar tratamientos')
            ->assertDontSee('Icono (clase o nombre)')
            // El campo queda vacío y sin leyendas: la vista previa en vivo muestra lo que verá la portada.
            ->assertDontSee('placeholder="Valoración', false)
            ->assertDontSee('Copiar al campo para editarlo')
            ->assertSee('data-vista-previa-descripcion', false)
            ->assertSee(config('dentissa.descripciones_servicios')['Odontología General']);
    }

    public function test_guardar_cambios_guarda_foto_y_tratamientos_a_la_vez(): void
    {
        Storage::fake('public');
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $valoracion = Tratamiento::create(['nombre' => 'Valoración', 'duracion_estimada' => 30, 'activo' => true]);
        $limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);

        $this->actingAs($this->admin)
            ->put(route('admin.servicios.update', $servicio), [
                'nombre' => 'Odontología General',
                'orden' => 1,
                'activo' => '1',
                // Una foto de celular típica (≈4 MB) ya no se rechaza.
                'imagen' => UploadedFile::fake()->image('consultorio.jpg', 1200, 1500)->size(4000),
                'tratamientos_enviados' => '1',
                'tratamiento_ids' => [$valoracion->id, $limpieza->id],
            ])
            ->assertRedirect(route('admin.servicios.index'))
            ->assertSessionHas('success');

        $servicio->refresh();
        $this->assertNotNull($servicio->imagen);
        Storage::disk('public')->assertExists($servicio->imagen);
        $this->assertEqualsCanonicalizing([$valoracion->id, $limpieza->id], $servicio->tratamientos()->pluck('tratamientos.id')->all());

        $this->actingAs($this->admin)
            ->get(route('admin.servicios.edit', $servicio))
            ->assertSee('Foto subida por la clínica.')
            ->assertSee('/storage/'.$servicio->imagen, false);
    }

    public function test_editar_no_cambia_disponible_y_quitar_todos_los_tratamientos_funciona(): void
    {
        $servicio = Servicio::create(['nombre' => 'Periodoncia', 'orden' => 3, 'activo' => true]);
        $servicio->tratamientos()->attach(Tratamiento::create(['nombre' => 'Raspado', 'duracion_estimada' => 60, 'activo' => true])->id, ['orden' => 1]);

        // 22/11/2026: "Disponible" solo se cambia con el interruptor de la lista.
        // Aunque llegue "activo=0" desde fuera del formulario, guardar no lo oculta.
        $this->actingAs($this->admin)
            ->put(route('admin.servicios.update', $servicio), [
                'nombre' => 'Periodoncia',
                'orden' => 3,
                'activo' => '0',
                'tratamientos_enviados' => '1',
            ])
            ->assertRedirect(route('admin.servicios.index'));

        $this->assertTrue($servicio->fresh()->activo);
        $this->assertSame(0, $servicio->tratamientos()->count());
    }

    public function test_los_tratamientos_inactivos_no_aparecen_ni_cuentan(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $valoracion = Tratamiento::create(['nombre' => 'Valoración', 'duracion_estimada' => 30, 'activo' => true]);
        $viejo = Tratamiento::create(['nombre' => 'Selladores de Fisuras', 'duracion_estimada' => 30, 'activo' => false]);
        $servicio->tratamientos()->attach([$valoracion->id => ['orden' => 1], $viejo->id => ['orden' => 2]]);

        $this->actingAs($this->admin)
            ->get(route('admin.servicios.edit', $servicio))
            ->assertOk()
            ->assertSee('Valoración')
            ->assertDontSee('Selladores de Fisuras')
            ->assertSee('seleccionados: 1', false);
    }

    public function test_no_se_puede_asignar_un_inactivo_y_su_vinculo_dormido_se_conserva(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $valoracion = Tratamiento::create(['nombre' => 'Valoración', 'duracion_estimada' => 30, 'activo' => true]);
        $viejo = Tratamiento::create(['nombre' => 'Selladores de Fisuras', 'duracion_estimada' => 30, 'activo' => false]);
        $otroInactivo = Tratamiento::create(['nombre' => 'Carillas de Porcelana', 'duracion_estimada' => 90, 'activo' => false]);
        $servicio->tratamientos()->attach($viejo->id, ['orden' => 1]);

        // Mandar un inactivo a mano se rechaza.
        $this->actingAs($this->admin)
            ->from(route('admin.servicios.edit', $servicio))
            ->put(route('admin.servicios.update', $servicio), [
                'nombre' => 'Odontología General', 'orden' => 1, 'activo' => '1',
                'tratamientos_enviados' => '1', 'tratamiento_ids' => [$otroInactivo->id],
            ])
            ->assertSessionHasErrors('tratamiento_ids.0');
        $this->assertFalse($servicio->tratamientos()->whereKey($otroInactivo->id)->exists());

        // Guardar solo con activos conserva el vínculo dormido del inactivo.
        $this->actingAs($this->admin)
            ->put(route('admin.servicios.update', $servicio), [
                'nombre' => 'Odontología General', 'orden' => 1, 'activo' => '1',
                'tratamientos_enviados' => '1', 'tratamiento_ids' => [$valoracion->id],
            ])
            ->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([$valoracion->id, $viejo->id], $servicio->tratamientos()->pluck('tratamientos.id')->all());

        // Al reactivarlo, vuelve a aparecer marcado en este servicio.
        $viejo->update(['activo' => true]);
        $this->actingAs($this->admin)
            ->get(route('admin.servicios.edit', $servicio))
            ->assertSee('Selladores de Fisuras')
            ->assertSee('seleccionados: 2', false);
    }

    public function test_avisa_si_un_tratamiento_tambien_esta_en_otros_servicios_sin_saturar(): void
    {
        $general = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $ortodoncia = Servicio::create(['nombre' => 'Ortodoncia', 'orden' => 2, 'activo' => true]);
        $periodoncia = Servicio::create(['nombre' => 'Periodoncia', 'orden' => 3, 'activo' => true]);
        $valoracion = Tratamiento::create(['nombre' => 'Valoración', 'duracion_estimada' => 30, 'activo' => true]);
        Tratamiento::create(['nombre' => 'Retiro de Caries', 'duracion_estimada' => 60, 'activo' => true]);
        foreach ([$general, $ortodoncia, $periodoncia] as $i => $servicio) {
            $servicio->tratamientos()->attach($valoracion->id, ['orden' => $i + 1]);
        }

        $html = $this->actingAs($this->admin)->get(route('admin.servicios.edit', $general))->assertOk()->getContent();

        // Un nombre + contador; la lista completa en el title. No se menciona el propio servicio.
        $this->assertStringContainsString('También en Ortodoncia +1', $html);
        $this->assertStringContainsString('title="También en: Ortodoncia, Periodoncia"', $html);
        // Un tratamiento que no está en otro servicio no lleva nota.
        $this->assertSame(1, substr_count($html, 'data-tambien-en'));
    }

    public function test_la_ruta_anterior_de_tratamientos_sigue_funcionando(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $valoracion = Tratamiento::create(['nombre' => 'Valoración', 'duracion_estimada' => 30, 'activo' => true]);

        $this->actingAs($this->admin)
            ->patch(route('admin.servicios.tratamientos.sync', $servicio), ['tratamiento_ids' => [$valoracion->id]])
            ->assertRedirect(route('admin.servicios.edit', $servicio));

        $this->assertSame([$valoracion->id], $servicio->tratamientos()->pluck('tratamientos.id')->all());
    }
}

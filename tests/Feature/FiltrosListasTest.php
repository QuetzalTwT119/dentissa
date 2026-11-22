<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Enums\TipoImagen;
use App\Models\Caso;
use App\Models\Imagen;
use App\Models\Oferta;
use App\Models\PreguntaFrecuente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 22/11/2026: el filtro global (x-filtro.barra) solo estaba en 6 listas. Con 22
 * ofertas de QA no había forma de buscar. Ahora también lo tienen Ofertas,
 * Tratamientos, Preguntas frecuentes, Casos y Galería.
 */
class FiltrosListasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => RolUsuario::Admin]);
    }

    private function ver(string $ruta, array $filtros = []): string
    {
        return $this->actingAs($this->admin)->get(route($ruta, $filtros))->assertOk()->getContent();
    }

    public function test_las_listas_que_crecen_tienen_el_filtro_global(): void
    {
        foreach (['admin.ofertas.index', 'admin.tratamientos.index', 'admin.preguntas-frecuentes.index', 'admin.casos.index', 'admin.galeria.index'] as $ruta) {
            $this->assertStringContainsString('data-filtro-barra', $this->ver($ruta), $ruta);
        }
    }

    public function test_ofertas_se_filtran_por_texto_estado_y_tratamiento(): void
    {
        $limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'activo' => true]);
        $base = ['descripcion' => 'Para pacientes nuevos y de siempre.', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 10,
            'fecha_inicio' => now()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString()];
        Oferta::create($base + ['titulo' => 'Limpieza express', 'id_tratamiento' => $limpieza->id, 'estado' => 'activa']);
        Oferta::create($base + ['titulo' => 'Blanqueamiento navideño', 'estado' => 'pendiente']);

        $html = $this->ver('admin.ofertas.index', ['buscar' => 'express']);
        $this->assertStringContainsString('Limpieza express', $html);
        $this->assertStringNotContainsString('Blanqueamiento navideño', $html);

        $html = $this->ver('admin.ofertas.index', ['estado' => 'pendiente']);
        $this->assertStringContainsString('Blanqueamiento navideño', $html);
        $this->assertStringNotContainsString('Limpieza express', $html);

        $html = $this->ver('admin.ofertas.index', ['tratamiento' => $limpieza->id]);
        $this->assertStringContainsString('Limpieza express', $html);
        $this->assertStringNotContainsString('Blanqueamiento navideño', $html);
        // El selector solo ofrece tratamientos que tienen ofertas.
        $this->assertStringContainsString('<option value="'.$limpieza->id.'" selected', $html);
    }

    public function test_tratamientos_preguntas_casos_y_galeria_se_filtran(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
        $limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'activo' => true]);
        Tratamiento::create(['nombre' => 'Carillas', 'duracion_estimada' => 60, 'activo' => false]);
        $servicio->tratamientos()->attach($limpieza);

        $html = $this->ver('admin.tratamientos.index', ['activo' => '0']);
        $this->assertStringContainsString('Carillas', $html);
        $this->assertStringNotContainsString('Limpieza Dental', $html);
        $html = $this->ver('admin.tratamientos.index', ['servicio' => $servicio->id]);
        $this->assertStringContainsString('Limpieza Dental', $html);
        $this->assertStringNotContainsString('Carillas', $html);

        PreguntaFrecuente::create(['pregunta' => '¿Atienden niños?', 'respuesta' => 'Sí, desde los 3 años.', 'activa' => true, 'orden' => 1]);
        PreguntaFrecuente::create(['pregunta' => '¿Hay estacionamiento?', 'respuesta' => 'No contamos con uno.', 'activa' => false, 'orden' => 2]);
        $html = $this->ver('admin.preguntas-frecuentes.index', ['buscar' => '3 años']);
        $this->assertStringContainsString('¿Atienden niños?', $html);
        $this->assertStringNotContainsString('estacionamiento', $html);
        $this->assertStringContainsString('estacionamiento', $this->ver('admin.preguntas-frecuentes.index', ['activa' => '0']));

        Caso::create(['titulo' => 'Blanqueamiento antes y después', 'activo' => true, 'orden' => 1]);
        Caso::create(['titulo' => 'Ortodoncia completa', 'activo' => false, 'orden' => 2]);
        $html = $this->ver('admin.casos.index', ['activo' => '1']);
        $this->assertStringContainsString('Blanqueamiento antes y después', $html);
        $this->assertStringNotContainsString('Ortodoncia completa', $html);

        Imagen::create(['titulo' => 'Recepción', 'url' => 'galeria/a.jpg', 'tipo' => TipoImagen::Galeria, 'orden' => 1, 'activa' => true]);
        Imagen::create(['titulo' => 'Consultorio 2', 'url' => 'galeria/b.jpg', 'tipo' => TipoImagen::Galeria, 'orden' => 2, 'activa' => true]);
        $html = $this->ver('admin.galeria.index', ['buscar' => 'Recep']);
        $this->assertStringContainsString('Recepción', $html);
        $this->assertStringNotContainsString('Consultorio 2', $html);
    }
}

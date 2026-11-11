<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImagenesPublicasTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_recursos_visuales_publicos_aprobados_existen(): void
    {
        $imagenes = config('dentissa.imagenes');

        $this->assertNotEmpty($imagenes['hero']);

        foreach ([$imagenes['hero'], ...array_values($imagenes['servicios'])] as $ruta) {
            $this->assertFileExists(public_path($ruta));
        }
    }

    public function test_la_portada_muestra_el_catalogo_visual_base_si_aun_no_hay_servicios(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Tratamientos diseñados para tu salud y armonía')
            ->assertSee('Valoración Integral')
            ->assertSee('/images/3.jpg');
    }

    public function test_la_portada_y_la_agenda_describen_los_tratamientos_reales_de_la_clinica(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Blanqueamiento Dental', 'Retiro de Caries &amp; Resinas', '1 hora', 'Extracción Cuidadosa'], false)
            ->assertDontSee('Hasta 2 horas')
            ->assertDontSee('Información por confirmar</h3>', false);

        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('Valoración, limpieza dental, retiro de caries y extracciones para toda la familia.')
            ->assertDontSee('carillas estéticas');
    }
}

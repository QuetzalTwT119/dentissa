<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Descripción para buscadores y vista previa al compartir (26/11/2026).
 *
 * Criterios de aceptación:
 * - La portada, Promociones y Agendar cita tienen descripción, dirección
 *   canónica y etiquetas Open Graph y Twitter.
 * - Cada página tiene su propio título y descripción.
 * - La imagen existe, mide 1200x630 y pesa menos de 300 KB (WhatsApp la
 *   descarta si es muy pesada).
 */
class VistaPreviaCompartirTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_paginas_publicas_tienen_descripcion_y_vista_previa(): void
    {
        $paginas = [
            '/' => 'Odontología integral y estética con la Dra. Melissa López N.',
            route('promociones.index', [], false) => 'Promociones vigentes en el consultorio',
            route('citas.publica.create', [], false) => 'Elige el tratamiento, el día y la hora en línea.',
        ];

        foreach ($paginas as $ruta => $descripcion) {
            $html = $this->get($ruta)->assertOk()->getContent();

            $this->assertSame(1, substr_count($html, '<meta name="description"'), "{$ruta}: una sola descripción");
            $this->assertStringContainsString($descripcion, $html, "{$ruta}: descripción propia");
            $this->assertStringContainsString('<link rel="canonical" href="'.url($ruta).'">', $html);
            foreach (['og:title', 'og:description', 'og:url', 'og:image', 'twitter:card'] as $etiqueta) {
                $this->assertStringContainsString($etiqueta, $html, "{$ruta}: falta {$etiqueta}");
            }
            $this->assertStringContainsString('content="'.asset('images/dentissa/vista-previa.jpg').'"', $html);
        }
    }

    public function test_la_imagen_de_vista_previa_tiene_el_tamano_recomendado(): void
    {
        $archivo = public_path('images/dentissa/vista-previa.jpg');

        $this->assertFileExists($archivo);
        [$ancho, $alto] = getimagesize($archivo);
        $this->assertSame([1200, 630], [$ancho, $alto]);
        $this->assertLessThan(300 * 1024, filesize($archivo));
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA de las vistas públicas en producción (26/11/2026, H-110).
 */
class VistasPublicasQaTest extends TestCase
{
    use RefreshDatabase;

    /** En celular el botón "Agendar cita" del encabezado no cabía junto a la marca (428 px en 375). */
    public function test_promociones_no_desborda_el_encabezado_en_celular_y_dice_cita(): void
    {
        $html = $this->get(route('promociones.index'))->assertOk()
            ->assertSee('Puedes agendar tu cita en línea')
            ->assertDontSee('agendar tu valoración')
            ->getContent();

        $this->assertMatchesRegularExpression('/class="[^"]*\bhidden\b[^"]*\bsm:inline-flex\b[^"]*" data-agendar-encabezado/', $html);
    }

    /** La página de acceso del personal no debe salir en los resultados de Google. */
    public function test_el_acceso_del_personal_pide_no_indexarse(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get(route('password.request'))->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get('/')->assertOk()->assertDontSee('noindex', false);
    }

    /** Índice de páginas públicas para Google, con el dominio de APP_URL. */
    public function test_sitemap_lista_las_paginas_publicas(): void
    {
        $respuesta = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('application/xml', $respuesta->headers->get('Content-Type'));
        $xml = simplexml_load_string($respuesta->getContent());
        $this->assertNotFalse($xml, 'El sitemap debe ser XML válido');

        $direcciones = [];
        foreach ($xml->url as $url) {
            $direcciones[] = (string) $url->loc;
        }

        $this->assertSame([
            url('/'),
            route('citas.publica.create'),
            route('promociones.index'),
            route('privacidad.aviso'),
        ], $direcciones);
        $this->assertStringNotContainsString(route('login'), $respuesta->getContent());
    }

    /** El 404 mencionaba la agenda sin un enlace para llegar a ella. */
    public function test_la_pagina_404_lleva_a_la_agenda(): void
    {
        $this->get('/pagina-que-no-existe')->assertNotFound()
            ->assertSee('href="/agendar-cita"', false)
            ->assertSee('agendar tu cita en línea');
    }

    /** En celular el asistente se esconde cuando la barra de botones de la agenda pasa por debajo. */
    public function test_el_asistente_no_tapa_los_botones_de_la_agenda(): void
    {
        $html = $this->get(route('citas.publica.create'))->assertOk()->getContent();

        $this->assertSame(4, substr_count($html, '<div data-evitar-asistente class="'));
        $this->assertStringContainsString('revisarBotones()', $html);
        $this->assertStringContainsString("(oculto || tapaBotones) ? 'translate-y-24 opacity-0 pointer-events-none'", $html);
    }
}

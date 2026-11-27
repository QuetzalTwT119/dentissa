<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pantallas grandes (27/11/2026, H-116): en 1920 px quedaban 344 px vacíos a cada
 * lado. Desde 1536 px (2xl) el sitio crece a 88rem y el teléfono sale en el encabezado.
 */
class PortadaPantallasGrandesTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_contenedores_crecen_en_pantallas_grandes(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Ninguna sección se quedó en 1216 px fijos: todas crecen juntas, alineadas con el encabezado.
        $this->assertSame(0, preg_match_all('/max-w-7xl(?! 2xl:max-w-\[88rem\])/', $html));
        $this->assertGreaterThanOrEqual(8, substr_count($html, 'max-w-7xl 2xl:max-w-[88rem]'));
    }

    public function test_el_telefono_sale_en_el_encabezado_solo_en_pantallas_grandes(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-telefono-encabezado', false)
            ->assertSee('aria-label="Llamar al consultorio: '.config('dentissa.contacto.telefono').'"', false)
            ->assertSee('hidden min-h-11 items-center gap-2 rounded-full px-3 text-sm font-semibold tabular-nums text-ink-soft transition-colors hover:text-brand-700 2xl:inline-flex', false);
    }
}

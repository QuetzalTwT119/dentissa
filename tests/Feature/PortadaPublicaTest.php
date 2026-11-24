<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortadaPublicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_portada_muestra_la_nueva_identidad_y_dirige_a_la_agenda_validada(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Dra. Melissa López')
            ->assertSee('explicada paso a paso')
            ->assertSee('Agendar mi valoración')
            ->assertSee('¿Prefieres llamar?')
            ->assertSee(config('dentissa.contacto.telefono_enlace'))
            ->assertSee('Elegir fecha y horario')
            ->assertSee(route('citas.publica.create'))
            ->assertSee('/images/dentissa/hero-inicio.jpg')
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('/images/9.jpg')
            ->assertSee('data-scrollspy-link', false)
            ->assertSee('updateActiveNavigation', false)
            ->assertSee('aria-current="location"', false)
            ->assertDontSee('https://fonts.googleapis.com')
            ->assertDontSee('/citas-publicas')
            ->assertDontSee('744 000 0000')
            ->assertDontSee('contacto@dentissa.mx')
            ->assertDontSee('+500 pacientes satisfechos');
    }
    /** 24/11/2026: "Tu primera visita" en 3 pasos y la ubicación con mapa de Google. */
    public function test_muestra_primera_visita_y_ubicacion_con_mapa(): void
    {
        config(['dentissa.contacto.direccion' => 'Blvd. Montebello 228, 78421 Pozos, S.L.P.']);

        $html = $this->get('/')->assertOk()
            ->assertSee('data-primera-visita', false)
            ->assertSeeInOrder(['Paso 01', 'Elige tu horario', 'Paso 02', 'Recibe la confirmación', 'Paso 03', 'Te esperamos'])
            ->assertSee('data-ubicacion', false)
            ->assertSee('Blvd. Montebello 228, 78421 Pozos, S.L.P.')
            ->getContent();

        $direccion = urlencode('Blvd. Montebello 228, 78421 Pozos, S.L.P.');
        $this->assertStringContainsString('https://maps.google.com/maps?q='.$direccion.'&amp;z=16&amp;output=embed', $html);
        $this->assertStringContainsString('https://www.google.com/maps/dir/?api=1&amp;destination='.$direccion, $html);
        $this->assertMatchesRegularExpression('#<iframe[^>]*loading="lazy"#', $html);

        // Sin dirección no hay mapa vacío.
        config(['dentissa.contacto.direccion' => '']);
        $this->get('/')->assertOk()->assertDontSee('data-ubicacion', false)->assertDontSee('<iframe', false);
    }
}

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
    /** 24/11/2026: lo que tenían los sitios de referencia y faltaba (sin certificados). */
    public function test_casos_whatsapp_flotante_y_mision_vision(): void
    {
        // Sin casos con foto, no hay sección vacía.
        $this->get('/')->assertOk()->assertDontSee('data-casos', false)
            ->assertSee('data-whatsapp-flotante', false)
            ->assertSee('data-mision-vision', false)
            ->assertSee(config('dentissa.contacto.mision'))
            ->assertSee(config('dentissa.contacto.vision'))
            ->assertDontSee('Certificad');

        $caso = \App\Models\Caso::create(['titulo' => 'Blanqueamiento de Ana', 'descripcion' => 'Dos sesiones.', 'activo' => true, 'orden' => 1]);
        foreach (['antes.jpg', 'despues.jpg'] as $i => $archivo) {
            \App\Models\Imagen::create(['titulo' => $archivo, 'url' => 'casos/'.$archivo, 'tipo' => \App\Enums\TipoImagen::Caso, 'referencia_id' => $caso->id, 'orden' => $i, 'activa' => true]);
        }
        \App\Models\Caso::create(['titulo' => 'Caso sin fotos', 'activo' => true, 'orden' => 2]);

        $this->get('/')->assertOk()
            ->assertSee('data-casos', false)
            ->assertSee('Blanqueamiento de Ana')
            ->assertSeeInOrder(['Antes', 'Después'])
            ->assertSee('storage/casos/antes.jpg', false)
            ->assertDontSee('Caso sin fotos');
    }
    /** 24/11/2026: Galería y Preguntas frecuentes del panel salen en la portada (antes no). */
    public function test_galeria_y_preguntas_del_panel_salen_en_la_portada(): void
    {
        // Sin fotos ni preguntas: las 2 fotos de fábrica y sin sección de preguntas.
        $html = $this->get('/')->assertOk()->assertDontSee('data-preguntas', false)->getContent();
        $this->assertSame(2, substr_count($html, 'data-foto-galeria'));
        $this->assertStringContainsString('/images/6.jpg', $html);

        foreach (['Sala de espera', 'Sillón dental', 'Recepción'] as $i => $titulo) {
            \App\Models\Imagen::create(['titulo' => $titulo, 'descripcion' => 'Foto '.$i, 'url' => "galeria/foto{$i}.jpg", 'tipo' => \App\Enums\TipoImagen::Galeria, 'orden' => $i, 'activa' => true]);
        }
        \App\Models\Imagen::create(['titulo' => 'Oculta', 'url' => 'galeria/oculta.jpg', 'tipo' => \App\Enums\TipoImagen::Galeria, 'orden' => 9, 'activa' => false]);
        \App\Models\PreguntaFrecuente::create(['pregunta' => '¿Atienden niños?', 'respuesta' => 'Sí, con odontopediatría.', 'activa' => true, 'orden' => 1]);
        \App\Models\PreguntaFrecuente::create(['pregunta' => 'Pregunta apagada', 'respuesta' => 'No sale.', 'activa' => false, 'orden' => 2]);

        $html = $this->get('/')->assertOk()
            ->assertSeeInOrder(['Sala de espera', 'Sillón dental', 'Recepción'])
            ->assertDontSee('Oculta')
            ->assertSee('data-preguntas', false)
            ->assertSee('¿Atienden niños?')
            ->assertSee('Sí, con odontopediatría.')
            ->assertDontSee('Pregunta apagada')
            ->getContent();
        $this->assertSame(3, substr_count($html, 'data-foto-galeria'));
        $this->assertStringContainsString('storage/galeria/foto0.jpg', $html);
        $this->assertStringNotContainsString('/images/6.jpg', $html);
        // Impar: la última ocupa todo el ancho.
        $this->assertStringContainsString('md:col-span-5', $html);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Servicio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortadaPublicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_portada_muestra_un_recorrido_claro_para_agendar_y_no_publica_contactos_de_ejemplo(): void
    {
        Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Tu sonrisa,')
            ->assertSee('Tres pasos para encontrar tu horario')
            ->assertSee('Explora nuestros servicios')
            ->assertDontSee('Tu cita, paso a paso')
            ->assertDontSee('fonts.bunny.net')
            ->assertSee('/fonts/figtree-latin-400-normal.woff2')
            ->assertSee('as="image" fetchpriority="high"', false)
            ->assertSee('/images/dentissa/hero-inicio.jpg')
            ->assertSee('data-asistente-zona-reservada class="mt-8 flex flex-col', false)
            ->assertSee('grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4', false)
            ->assertSee('aria-label="Servicios dentales"', false)
            ->assertDontSee('servicios-marquee')
            ->assertSee('servicios disponibles')
            ->assertSee(route('citas.publica.create'))
            ->assertDontSee('Presentación de demostración')
            ->assertSee('Información ficticia · No contactar')
            ->assertSee('+52 55 0000 0000')
            ->assertSee('hola@dentissa.demo')
            ->assertSee('Telegram de prueba')
            ->assertSee('@dentissa_demo')
            ->assertDontSee('tel:+52 55 0000 0000')
            ->assertDontSee('mailto:hola@dentissa.demo')
            ->assertDontSee('WhatsApp');
    }

    public function test_la_portada_muestra_telegram_solo_cuando_se_configura(): void
    {
        Servicio::create(['nombre' => 'Odontología General', 'activo' => true]);
        config([
            'dentissa.modo_demo' => false,
            'dentissa.contacto.telefono' => null,
            'dentissa.contacto.correo' => null,
            'dentissa.contacto.direccion' => null,
            'dentissa.contacto.horario_texto' => null,
            'dentissa.contacto.telegram_url' => 'https://t.me/dentissa_prueba',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Contactar por Telegram')
            ->assertSee('https://t.me/dentissa_prueba');
    }
}

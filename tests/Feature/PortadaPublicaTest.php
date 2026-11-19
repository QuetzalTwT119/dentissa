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
}

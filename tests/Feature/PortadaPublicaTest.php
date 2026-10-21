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
            ->assertSee('Tu sonrisa merece una experiencia')
            ->assertSee('Elegir fecha y horario')
            ->assertSee(route('citas.publica.create'))
            ->assertSee('/images/5.jpg')
            ->assertSee('/images/9.jpg')
            ->assertDontSee('https://fonts.googleapis.com')
            ->assertDontSee('/citas-publicas')
            ->assertDontSee('744 000 0000')
            ->assertDontSee('contacto@dentissa.mx')
            ->assertDontSee('+500 pacientes satisfechos');
    }
}

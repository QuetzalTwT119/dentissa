<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Detalles de la segunda revisión de las vistas públicas (26/11/2026, H-112).
 */
class DetallesPublicosTest extends TestCase
{
    use RefreshDatabase;

    /** El paso 1 decía "Selección clínica" y el resumen "Tipo de atención": un solo nombre por paso. */
    public function test_los_pasos_de_la_agenda_se_llaman_igual_arriba_y_en_el_resumen(): void
    {
        $this->get(route('citas.publica.create'))->assertOk()
            ->assertDontSee('Selección clínica')
            ->assertSee("pasos: ['Tipo de atención', 'Día', 'Hora',", false)
            ->assertSeeInOrder([
                "etiqueta: 'Tipo de atención'",
                "etiqueta: 'Día'",
                "etiqueta: 'Hora'",
                "etiqueta: 'Datos'",
            ], false);
    }

    /** Cada pantalla de acceso tiene su propio título en la pestaña. */
    public function test_cada_pantalla_de_acceso_tiene_su_titulo(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('<title>Acceso · Melissa López N. — Odontología Integral</title>', false);
        $this->get(route('password.request'))->assertOk()
            ->assertSee('<title>Recuperar contraseña · Melissa López N. — Odontología Integral</title>', false);
    }

    /** Guía de diseño responsivo: la foto de fábrica del inicio va en WebP con el JPG de respaldo. */
    public function test_la_foto_del_inicio_se_sirve_en_webp_con_respaldo_jpg(): void
    {
        $this->assertFileExists(public_path('images/dentissa/hero-inicio.webp'));
        $this->assertLessThan(filesize(public_path('images/dentissa/hero-inicio.jpg')), filesize(public_path('images/dentissa/hero-inicio.webp')));

        $this->get('/')->assertOk()->assertSeeInOrder([
            '<picture>',
            '<source type="image/webp" srcset="'.asset('images/dentissa/hero-inicio.webp').'">',
            asset('images/dentissa/hero-inicio.jpg'),
            '</picture>',
        ], false);
    }

    /** El aviso de privacidad no tenía la descripción que muestra Google. */
    public function test_el_aviso_de_privacidad_tiene_descripcion(): void
    {
        $this->get(route('privacidad.aviso'))->assertOk()
            ->assertSee('<meta name="description" content="Cómo cuida el consultorio', false)
            ->assertSee('<link rel="canonical" href="'.route('privacidad.aviso').'">', false);
    }
}

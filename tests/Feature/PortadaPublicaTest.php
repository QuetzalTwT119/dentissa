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
            ->assertSee('Agendar mi cita')
            ->assertDontSee('Agendar mi valoración')
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
    /** 24/11/2026: el enlace va al acceso del personal; los pacientes no tienen cuenta. */
    public function test_el_enlace_de_acceso_es_para_el_personal_y_no_para_pacientes(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Acceso del personal')
            ->assertSee(route('login'), false)
            ->assertDontSee('Portal Paciente');

        $this->actingAs(\App\Models\User::factory()->create())->get('/')->assertOk()
            ->assertSee('Ir al panel')
            ->assertDontSee('>Dashboard<', false);
    }

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
            // 27/11/2026: WhatsApp ya no es botón flotante aparte; va en el menú de ayuda.
            ->assertDontSee('data-whatsapp-flotante', false)
            ->assertSee('data-menu-ayuda', false)
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
        // Sin fotos ni preguntas: las 2 ilustraciones de fábrica y sin sección de preguntas.
        $html = $this->get('/')->assertOk()->assertDontSee('data-preguntas', false)->getContent();
        $this->assertSame(2, substr_count($html, 'data-foto-galeria'));
        $this->assertStringContainsString('/images/ilustraciones/sala-espera.svg', $html);

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
        $this->assertStringNotContainsString('/images/ilustraciones/sala-espera.svg', $html);
        // Impar: la última ocupa todo el ancho.
        $this->assertStringContainsString('md:col-span-5', $html);
    }

    /** 25/11/2026 (QA en producción): el pie listaba tratamientos que no existían en el catálogo. */
    public function test_el_pie_muestra_las_especialidades_reales_del_catalogo(): void
    {
        \App\Models\Servicio::query()->update(['activo' => false]);
        \App\Models\Servicio::create(['nombre' => 'Especialidad Real Uno', 'activo' => true, 'orden' => 1]);
        \App\Models\Servicio::create(['nombre' => 'Especialidad Real Dos', 'activo' => true, 'orden' => 2]);

        $html = $this->get('/')->assertOk()->getContent();
        $pie = substr($html, strrpos($html, '<footer'));

        $this->assertStringContainsString('Especialidad Real Uno', $pie);
        $this->assertStringContainsString('Especialidad Real Dos', $pie);
        $this->assertStringNotContainsString('Resinas Biocompatibles', $pie);
        $this->assertStringNotContainsString('Extracciones Suaves', $pie);
    }

    /** 25/11/2026 (QA en producción): en celular el teléfono del inicio quedaba bajo el botón flotante de WhatsApp. */
    public function test_en_celular_el_telefono_del_inicio_va_debajo_del_boton(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<a[^>]*class="[^"]*\bw-full\b[^"]*\bsm:w-auto\b[^"]*"[^>]*data-hero-telefono/', $html);
    }

    /** 25/11/2026 (QA en producción): las fotos pesaban ~650 KB cada una (5 MB la portada). */
    public function test_las_fotos_de_la_portada_son_ligeras(): void
    {
        foreach (glob(public_path('images/dentissa/*.jpg')) as $foto) {
            $this->assertLessThan(150 * 1024, filesize($foto), basename($foto).' pesa más de 150 KB: comprímela antes de subirla.');
        }
    }

    /**
     * 26/11/2026: textos para pacientes, no de manual técnico, y el menú dice
     * «El consultorio» (la sección no tiene fotos de instalaciones).
     */
    public function test_la_filosofia_y_el_menu_hablan_en_lenguaje_de_paciente(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('El consultorio')
            ->assertDontSee('>Instalaciones<', false)
            ->assertSee('Tu cita empieza a tiempo:')
            ->assertSee('Instrumental estéril en cada consulta:')
            ->assertSee('Tu expediente, siempre a la mano:')
            ->assertDontSee('Bloqueo dinámico')
            ->assertDontSee('dentro del sistema de la clínica');
    }

    /** 26/11/2026: el menú "Contacto" caía en el formulario de opiniones porque dos elementos se llamaban "contacto". */
    public function test_cada_id_de_la_portada_es_unico_y_contacto_es_el_pie(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('/\sid="([^"]+)"/', $html, $coincidencias);
        $repetidos = array_keys(array_filter(array_count_values($coincidencias[1]), fn ($veces) => $veces > 1));

        $this->assertSame([], $repetidos, 'IDs repetidos en la portada: '.implode(', ', $repetidos));
        $this->assertMatchesRegularExpression('/<footer id="contacto"/', $html);
    }

    /** 26/11/2026: el pie toma el correo de Configuración y el acceso del personal se llama igual que en el encabezado. */
    public function test_el_pie_usa_el_correo_configurado_y_el_mismo_acceso_del_personal(): void
    {
        config(['dentissa.contacto.correo' => 'citas@consultorio-prueba.mx']);

        $pie = str($this->get('/')->assertOk()->getContent())->after('<footer id="contacto"')->toString();

        $this->assertStringContainsString('mailto:citas@consultorio-prueba.mx', $pie);
        $this->assertStringNotContainsString('trabajout745@gmail.com', $pie);
        // 27/11/2026: sin los botones «WhatsApp» y «Acceso del personal» (este sigue en el
        // encabezado); WhatsApp queda como enlace de la lista y el aviso no se repite.
        $this->assertStringNotContainsString('Acceso del personal', $pie);
        $this->assertStringNotContainsString('rounded-full bg-sage-500 px-5', $pie);
        $this->assertStringContainsString('data-pie-whatsapp', $pie);
        $this->assertSame(1, substr_count($pie, '>Aviso de privacidad<'));
        $this->assertStringNotContainsString('Portal →', $pie);
        $this->assertSame(1, substr_count($pie, 'Atención con previa cita'));
    }

    /** 26/11/2026: textos en español (sin "&" ni mayúsculas a la inglesa), menú "La doctora" y "cita" en vez de "valoración". */
    public function test_los_textos_de_la_portada_siguen_el_estilo_del_espanol(): void
    {
        $html = $this->get('/')->assertOk()
            ->assertSee('Atención humana y sin prisas')
            ->assertSee('Instrumental estéril y preciso')
            ->assertSee('Reserva fácil y rápida')
            ->assertSee('para tu cita en Dentissa')
            ->assertDontSee('para tu valoración en Dentissa')
            ->assertDontSee('La Clínica')
            ->getContent();

        $this->assertStringContainsString('href="#nosotros"', $html);
        $this->assertMatchesRegularExpression('/href="#nosotros"[^>]*>\s*La doctora/', $html);

        // Ningún "&" suelto en el texto visible (los de URLs van como &amp;).
        $texto = strip_tags(preg_replace('#<(script|style)\b.*?</\1>#s', '', $html));
        $this->assertDoesNotMatchRegularExpression('/\s&\s/', $texto);
    }

    /** 26/11/2026: el primer título de la página es el <h1> de la portada, no el del asistente. */
    public function test_el_primer_titulo_de_la_pagina_es_el_h1(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('/<h[1-6]\b/', $html, $primero);
        $this->assertSame('<h1', $primero[0] ?? null);
        $this->assertStringContainsString('Dentissa Asistente', $html);
    }

    /** 26/11/2026: el formulario de opiniones va plegado y se abre solo si regresa con errores. */
    public function test_el_formulario_de_opiniones_va_plegado_salvo_con_errores(): void
    {
        $plegado = $this->get('/')->assertOk()
            ->assertSee('data-abrir-testimonio', false)
            ->assertSee('Escribir mi opinión')
            ->getContent();
        $this->assertMatchesRegularExpression('/<form id="formulario-testimonio" x-show="abierto"\s+x-cloak/', $plegado, 'Sin errores el formulario debe ir plegado');
        $this->assertStringContainsString('estrellas: 0, abierto: false', $plegado);

        // Envío real con datos incompletos: regresa a la portada con los errores en la sesión.
        $conErrores = $this->followingRedirects()
            ->post(route('testimonios.publico.store'), ['comentario' => 'corto'])
            ->assertOk()->getContent();
        $this->assertStringContainsString('estrellas: 0, abierto: true', $conErrores, 'Con errores el formulario debe abrirse');
        $this->assertDoesNotMatchRegularExpression('/<form id="formulario-testimonio" x-show="abierto"\s+x-cloak/', $conErrores, 'Con errores no lleva x-cloak');
    }

    /**
     * 26/11/2026: en celular WhatsApp se escondía al bajar.
     * 27/11/2026: un solo botón flotante («¿Necesitas ayuda?») con WhatsApp, llamar y el
     * asistente en su menú; se sigue escondiendo al bajar en celular.
     */
    public function test_un_solo_boton_flotante_con_whatsapp_llamar_y_asistente(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'data-boton-ayuda'));
        $this->assertStringNotContainsString('data-whatsapp-flotante', $html);

        $menu = str($html)->after('data-menu-ayuda')->before('data-boton-ayuda')->toString();
        $this->assertStringContainsString('https://wa.me/', $menu);
        $this->assertStringContainsString('href="'.config('dentissa.contacto.telefono_enlace').'"', $menu);
        $this->assertStringContainsString('Preguntar al asistente', $menu);

        $this->assertStringContainsString('@abrir-asistente.window', $html);
        $this->assertStringContainsString('window.innerWidth >= 640', $html);
    }
}

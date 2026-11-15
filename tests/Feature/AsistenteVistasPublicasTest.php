<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenteVistasPublicasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_chat_esta_disponible_en_todas_las_vistas_publicas_de_atencion_al_paciente(): void
    {
        foreach ([
            'inicio' => url('/'),
            'agenda' => route('citas.publica.create'),
        ] as $vista => $url) {
            $respuesta = $this->get($url)->assertOk();

            $this->assertStringContainsString('Dentissa Asistente', $respuesta->getContent(), "Falta el asistente en la vista de {$vista}.");
            $this->assertSame(1, substr_count($respuesta->getContent(), 'id="consulta-inicio"'), "La vista de {$vista} debe incluir exactamente un chat.");
            // Regresión: la portada no tiene <meta name="csrf-token">; el chat debe llevar su propio token.
            $this->assertStringContainsString("'X-CSRF-TOKEN': '".csrf_token()."'", $respuesta->getContent(), "El chat de {$vista} debe incluir su token CSRF.");
            $this->assertStringNotContainsString('meta[name=csrf-token]', $respuesta->getContent(), "El chat de {$vista} no debe depender de la meta csrf-token.");
        }
    }

    public function test_el_widget_permanece_fijo_y_estable_con_acceso_plegado_y_panel_responsivo(): void
    {
        $contenido = $this->get(route('citas.publica.create'))->assertOk()->getContent();

        $this->assertStringContainsString('crearConversacionId()', $contenido);
        $this->assertStringContainsString("'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'", $contenido);
        $this->assertStringContainsString('fixed bottom-6 right-4', $contenido);
        $this->assertStringContainsString('border-2 border-white', $contenido);
        $this->assertStringContainsString('bg-brand-600 text-white', $contenido);
        $this->assertStringContainsString('shadow-black/15', $contenido);
        $this->assertStringNotContainsString('shadow-brand-100', $contenido, 'El acceso no debe tener resplandor ni sombra coloreada.');
        $this->assertStringContainsString('hover || enfocado', $contenido, 'El botón debe desplegar su nombre al pasar el cursor o enfocar con teclado.');
        $this->assertStringContainsString('whitespace-nowrap', $contenido);
        $this->assertStringContainsString('pb-28 sm:pb-16', $contenido);
        $this->assertStringContainsString('gap-6 px-4 sm:px-6', $contenido);
        $this->assertStringNotContainsString('<div class="pt-6">', $contenido, 'El asistente fijo no debe reservar espacio vertical en la página.');
        $this->assertStringNotContainsString('recalcularPosicion()', $contenido, 'El asistente permanece fijo sin recalcular posición en scroll.');
        $this->assertStringNotContainsString('desplazamientoBoton', $contenido, 'El botón permanece estable sin desplazarse con el scroll.');
        $this->assertStringContainsString('¿Qué tipo de atención necesitas?', $contenido);
        $this->assertSame(1, substr_count($contenido, 'id="consulta-inicio"'));
    }

    public function test_la_agenda_aprovecha_el_espacio_de_escritorio_con_un_resumen_responsivo(): void
    {
        $contenido = $this->get(route('citas.publica.create'))->assertOk()->getContent();

        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.75fr)]', $contenido);
        $this->assertStringContainsString('Tu cita, paso a paso', $contenido);
        $this->assertStringContainsString('aria-label="Volver al inicio"', $contenido);
        $this->assertStringContainsString('Inicio', $contenido);
        $this->assertStringContainsString('x-text="servicioSeleccionado.nombre"', $contenido);
        $this->assertStringContainsString('role="progressbar"', $contenido);
        $this->assertStringContainsString('Paso ${paso + 1} de ${pasos.length}', $contenido);
        $this->assertStringContainsString('x-if="!servicioSeleccionado"', $contenido);
        $this->assertStringContainsString('@click="idServicio = \'\'; idTratamiento = \'\'"', $contenido);
        $this->assertStringNotContainsString('>×</span>', $contenido);
        $this->assertStringContainsString('grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5', $contenido);
        $this->assertStringContainsString('x-text="servicioSeleccionado?.nombre ?? \'Pendiente\'"', $contenido);
        $this->assertStringContainsString('x-text="tratamientoSeleccionado?.nombre ?? \'Pendiente\'"', $contenido);
    }
}

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
            'atencion urgente' => route('atencion.urgencia'),
            'lista de espera' => route('atencion.lista-espera'),
        ] as $vista => $url) {
            $respuesta = $this->get($url)->assertOk();

            $this->assertStringContainsString('Dentissa Asistente', $respuesta->getContent(), "Falta el asistente en la vista de {$vista}.");
            $this->assertSame(1, substr_count($respuesta->getContent(), 'id="consulta-inicio"'), "La vista de {$vista} debe incluir exactamente un chat.");
        }
    }

    public function test_el_widget_funciona_en_http_lan_y_se_reubica_sin_desaparecer_al_cruzarse_con_contenido(): void
    {
        $contenido = $this->get(route('citas.publica.create'))->assertOk()->getContent();

        $this->assertStringContainsString('crearConversacionId()', $contenido);
        $this->assertStringContainsString("'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'", $contenido);
        $this->assertStringContainsString('fixed bottom-6 right-4', $contenido);
        $this->assertStringContainsString('inline-flex items-center gap-2 rounded-full border border-brand-100 bg-white', $contenido);
        $this->assertStringContainsString('h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600', $contenido);
        $this->assertStringContainsString('whitespace-nowrap leading-none', $contenido);
        $this->assertStringContainsString('pb-24 sm:pb-0', $contenido);
        $this->assertStringContainsString('px-4 pt-2 sm:px-6', $contenido);
        $this->assertStringNotContainsString('<div class="pt-6">', $contenido, 'El asistente fijo no debe reservar espacio vertical en la página.');
        $this->assertStringContainsString('window.innerWidth <= 639 ? 24 : 20', $contenido);
        $this->assertStringContainsString('recalcularPosicion()', $contenido);
        $this->assertStringContainsString(':style="`bottom: ${desplazamientoBoton}px`"', $contenido);
        $this->assertStringContainsString('gap-2 sm:bottom-5', $contenido, 'El widget debe mantenerse compacto y cercano al borde inferior.');
        $this->assertStringContainsString('zonaRect.top < botonTop + botonRect.height', $contenido, 'El asistente debe comprobar y evitar el solapamiento vertical con los campos visibles.');
        $this->assertStringContainsString('for (let iteracion = 0; iteracion < zonasVisibles.length; iteracion++)', $contenido, 'El asistente debe seguir buscando un sitio libre si al moverse encuentra otro campo.');
        $this->assertStringNotContainsString('x-show="!ocultarBoton || abierto"', $contenido);
        $this->assertStringContainsString('¿Qué tipo de atención necesitas?', $contenido);
        $this->assertSame(1, substr_count($contenido, 'id="consulta-inicio"'));
    }

    public function test_los_accesos_secundarios_de_urgencia_y_lista_de_espera_van_despues_de_la_agenda(): void
    {
        $contenido = $this->get(route('citas.publica.create'))->assertOk()->getContent();
        $asistente = strpos($contenido, 'id="consulta-inicio"');
        $agenda = strpos($contenido, '¿Qué tipo de atención necesitas?');
        $panel = strpos($contenido, '<aside data-asistente-zona-reservada');
        $urgencia = strpos($contenido, '¿Dolor intenso o urgencia?');
        $listaEspera = strpos($contenido, '¿No encuentras horario?');
        $panelCierre = $panel === false ? false : strpos($contenido, '</aside>', $panel);

        $this->assertNotFalse($asistente);
        $this->assertNotFalse($agenda);
        $this->assertNotFalse($panel);
        $this->assertNotFalse($urgencia);
        $this->assertNotFalse($listaEspera);
        $this->assertNotFalse($panelCierre);
        $contenidoPanel = substr($contenido, $panel, $panelCierre - $panel);
        $this->assertLessThan($agenda, $asistente, 'El asistente debe seguir disponible antes del flujo principal.');
        $this->assertLessThan($urgencia, $agenda, 'La agenda debe presentarse antes de las opciones secundarias.');
        $this->assertStringContainsString('¿Dolor intenso o urgencia?', $contenidoPanel, 'El acceso de urgencia debe quedar dentro del panel lateral.');
        $this->assertStringContainsString('¿No encuentras horario?', $contenidoPanel, 'La lista de espera debe quedar dentro del panel lateral.');
        $this->assertLessThan($listaEspera, $urgencia, 'Urgencia y lista de espera deben quedar juntas al final.');
        $this->assertStringContainsString('max-w-6xl', $contenido, 'La agenda y las opciones secundarias deben aprovechar mejor el ancho disponible.');
        $this->assertStringContainsString('sm:grid-cols-2', $contenido, 'Las opciones secundarias deben mostrarse en columnas en escritorio.');
    }

    public function test_la_agenda_aprovecha_el_espacio_de_escritorio_con_un_resumen_responsivo(): void
    {
        $contenido = $this->get(route('citas.publica.create'))->assertOk()->getContent();

        $this->assertStringContainsString('lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.8fr)]', $contenido);
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
        $this->assertStringContainsString('data-asistente-zona-reservada', $contenido);
    }

    public function test_el_asistente_no_cubre_tarjetas_ni_acciones_importantes_en_vistas_publicas(): void
    {
        foreach ([
            url('/') => ['Reservar mi valoración', 1],
            route('atencion.urgencia') => ['Enviar solicitud', 6],
            route('atencion.lista-espera') => ['Unirme a la lista de espera', 9],
        ] as $url => [$textoAccion, $zonasEsperadas]) {
            $contenido = $this->get($url)->assertOk()->getContent();
            $posicion = strpos($contenido, $textoAccion);

            $this->assertNotFalse($posicion, "No se encontró la zona reservada de la vista {$url}.");
            $inicioZona = strrpos(substr($contenido, 0, $posicion), 'data-asistente-zona-reservada');
            $this->assertNotFalse($inicioZona, "La acción de la vista {$url} no protege su zona del asistente flotante.");
            $this->assertGreaterThanOrEqual(
                $zonasEsperadas,
                substr_count($contenido, 'data-asistente-zona-reservada'),
                "La vista {$url} debe proteger cada campo o acción que puede quedar bajo el botón flotante."
            );
        }
    }
}

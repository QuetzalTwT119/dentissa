<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * 21/11/2026: los formularios del panel tenían "Volver al listado" arriba y
 * "Cancelar"/"Volver" abajo (lo mismo dos veces). Queda un solo "Cancelar".
 * Y "Guardar cambios" empieza desactivado hasta que algo cambie (app.js, global).
 */
class BotonesFormularioTest extends TestCase
{
    public function test_ningun_formulario_repite_el_boton_de_regresar(): void
    {
        $vistas = array_merge(
            glob(resource_path('views/admin/*/{create,edit,_encabezado}.blade.php'), GLOB_BRACE),
            glob(resource_path('views/citas/{create,edit}.blade.php'), GLOB_BRACE),
        );
        $this->assertNotEmpty($vistas);

        foreach ($vistas as $vista) {
            $html = file_get_contents($vista);
            $nombre = str_replace(resource_path('views/'), '', $vista);

            $this->assertStringNotContainsString('Volver al listado', $html, $nombre);
            $this->assertStringNotContainsString('Volver a la agenda', $html, $nombre);
            $this->assertStringNotContainsString('Volver al historial', $html, $nombre);
            $this->assertStringNotContainsString('<x-secondary-button type="button">Volver</x-secondary-button>', $html, $nombre);
        }
    }

    /** 22/11/2026: en los botones de solo ícono, el indicador de envío reemplaza al ícono (antes quedaba apilado). */
    public function test_el_indicador_de_envio_reemplaza_al_icono(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('button.place-items-center[aria-busy="true"] > :not(.btn-spinner)', $css);
        // Las listas ya no se desplazan por dentro de la tarjeta.
        $this->assertStringNotContainsString('.panel-contenido .tabla-larga.tabla-panel > [x-ref="desplazable"]', $css);
    }

    public function test_guardar_cambios_espera_a_que_algo_cambie(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("['PUT', 'PATCH'].includes(metodo)", $js);
        $this->assertStringContainsString('No hay cambios por guardar', $js);
        $this->assertStringContainsString('sinEsperarCambios', $js);
        // Incluye los botones de fuera del formulario (form="…").
        $this->assertStringContainsString('button[form="${CSS.escape(form.id)}"]', $js);
    }
}

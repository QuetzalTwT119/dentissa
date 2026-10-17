<?php

namespace Tests\Feature;

use Tests\TestCase;

class ImagenesPublicasTest extends TestCase
{
    public function test_los_recursos_visuales_publicos_aprobados_existen(): void
    {
        $imagenes = config('dentissa.imagenes');

        $this->assertNotEmpty($imagenes['hero']);

        foreach ([$imagenes['hero'], $imagenes['urgencias'], $imagenes['lista_espera'], ...array_values($imagenes['servicios'])] as $ruta) {
            $this->assertFileExists(public_path($ruta));
        }
    }
}

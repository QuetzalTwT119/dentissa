<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PwaArchivosPublicosTest extends TestCase
{
    public function test_el_manifiesto_y_los_recursos_de_instalacion_existen(): void
    {
        $publico = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR;
        $manifest = json_decode(file_get_contents($publico.'manifest.webmanifest'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Dentissa', $manifest['short_name']);
        $this->assertFileExists($publico.'sw.js');
        $this->assertFileExists($publico.'offline.html');

        foreach ($manifest['icons'] as $icono) {
            $this->assertFileExists($publico.ltrim($icono['src'], '/'));
        }
    }
}

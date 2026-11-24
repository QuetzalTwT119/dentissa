<?php

namespace Tests\Unit;

use App\Support\DiasFestivos;
use PHPUnit\Framework\TestCase;

class DiasFestivosTest extends TestCase
{
    public function test_2027_con_los_lunes_festivos_calculados(): void
    {
        $this->assertSame([
            '2027-01-01' => 'Año Nuevo',
            '2027-02-01' => 'Día de la Constitución',      // 1.er lunes de febrero
            '2027-03-15' => 'Natalicio de Benito Juárez',  // 3.er lunes de marzo
            '2027-05-01' => 'Día del Trabajo',
            '2027-09-16' => 'Día de la Independencia',
            '2027-11-02' => 'Día de Muertos',
            '2027-11-15' => 'Día de la Revolución',        // 3.er lunes de noviembre
            '2027-12-12' => 'Día de la Virgen de Guadalupe',
            '2027-12-24' => 'Nochebuena',
            '2027-12-25' => 'Navidad',
            '2027-12-31' => 'Fin de año',
        ], DiasFestivos::delAnio(2027));
    }

    public function test_los_lunes_cambian_cada_anio(): void
    {
        $festivos = DiasFestivos::delAnio(2026);

        $this->assertSame('Día de la Constitución', $festivos['2026-02-02']);
        $this->assertSame('Natalicio de Benito Juárez', $festivos['2026-03-16']);
        $this->assertSame('Día de la Revolución', $festivos['2026-11-16']);
    }

    public function test_el_1_de_octubre_solo_con_cambio_de_presidente(): void
    {
        $this->assertArrayHasKey('2030-10-01', DiasFestivos::delAnio(2030));
        $this->assertArrayHasKey('2036-10-01', DiasFestivos::delAnio(2036));
        $this->assertArrayNotHasKey('2027-10-01', DiasFestivos::delAnio(2027));
        $this->assertCount(11, DiasFestivos::delAnio(2027));
        $this->assertCount(12, DiasFestivos::delAnio(2030));
    }
}

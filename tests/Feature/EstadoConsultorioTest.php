<?php

namespace Tests\Feature;

use App\Models\HorarioAtencion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Abierto hoy hasta las 18:00" de la portada: sale de horarios_atencion
 * (por defecto L–V 8–18, sábado 9–14, domingo cerrado), no de un texto fijo.
 */
class EstadoConsultorioTest extends TestCase
{
    use RefreshDatabase;

    private function estadoA(string $fechaHora): ?array
    {
        return HorarioAtencion::estadoActual(Carbon::parse($fechaHora, 'America/Mexico_City'));
    }

    public function test_durante_el_horario_dice_hasta_que_hora_abre(): void
    {
        $this->assertSame(['abierto' => true, 'texto' => 'Abierto hoy hasta las 18:00'], $this->estadoA('2030-01-09 10:00'));
        $this->assertSame(['abierto' => true, 'texto' => 'Abierto hoy hasta las 14:00'], $this->estadoA('2030-01-12 13:59'));
    }

    public function test_antes_de_abrir_dice_a_que_hora_abre(): void
    {
        $this->assertSame(['abierto' => false, 'texto' => 'Abre hoy a las 8:00'], $this->estadoA('2030-01-09 07:30'));
    }

    public function test_despues_de_cerrar_dice_el_siguiente_dia_que_abre(): void
    {
        // Miércoles 18:00 → jueves; sábado 14:00 → salta el domingo cerrado.
        $this->assertSame(['abierto' => false, 'texto' => 'Cerrado ahora · abre mañana a las 8:00'], $this->estadoA('2030-01-09 18:00'));
        $this->assertSame(['abierto' => false, 'texto' => 'Cerrado ahora · abre el lunes a las 8:00'], $this->estadoA('2030-01-12 15:00'));
    }

    public function test_respeta_los_cambios_del_panel_de_horarios(): void
    {
        HorarioAtencion::where('dia_semana', 4)->update(['abierto' => false]);

        $this->assertSame(['abierto' => false, 'texto' => 'Cerrado ahora · abre el viernes a las 8:00'], $this->estadoA('2030-01-09 19:00'));
    }

    public function test_sin_dias_abiertos_no_muestra_nada(): void
    {
        HorarioAtencion::query()->update(['abierto' => false]);

        $this->assertNull($this->estadoA('2030-01-09 10:00'));
    }

    public function test_la_portada_muestra_el_estado_con_la_hora_de_la_clinica(): void
    {
        Carbon::setTestNow(Carbon::parse('2030-01-09 10:00', 'America/Mexico_City'));

        try {
            $this->get('/')->assertOk()->assertSee('Abierto hoy hasta las 18:00');
        } finally {
            Carbon::setTestNow();
        }
    }
}

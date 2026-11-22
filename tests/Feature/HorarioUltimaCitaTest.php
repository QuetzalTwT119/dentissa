<?php

namespace Tests\Feature;

use App\Models\HorarioAtencion;
use App\Models\Tratamiento;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 22/11/2026 (encontrado por el usuario al revisar los horarios):
 *  1. El margen de limpieza de los tratamientos largos o quirúrgicos se exigía
 *     también antes del cierre: una extracción de 60 min terminaba de ofrecerse
 *     a las 16:30 y no a las 17:00 (abre 8, cierra 18).
 *  2. Al guardar, el servidor solo revisaba el cupo: aceptaba citas a las 22:00,
 *     a las 06:00 o que terminaban después del cierre.
 */
class HorarioUltimaCitaTest extends TestCase
{
    use RefreshDatabase;

    private DisponibilidadService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-27 07:00:00'); // viernes, antes de abrir
        HorarioAtencion::query()->delete();
        HorarioAtencion::create(['dia_semana' => 1, 'abierto' => true, 'hora_apertura' => 8, 'hora_cierre' => 18, 'capacidad_simultanea' => 2]);
        HorarioAtencion::create(['dia_semana' => 0, 'abierto' => false, 'hora_apertura' => 8, 'hora_cierre' => 18, 'capacidad_simultanea' => 2]);
        $this->servicio = app(DisponibilidadService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tratamiento(int $minutos, ?string $categoria = null): Tratamiento
    {
        return Tratamiento::create(['nombre' => 'T'.$minutos.$categoria, 'duracion_estimada' => $minutos, 'categoria' => $categoria, 'activo' => true]);
    }

    public function test_la_ultima_cita_termina_al_cierre_sin_exigir_el_margen_de_limpieza(): void
    {
        $lunes = '2026-11-30';

        $this->assertSame('17:30', last($this->servicio->horariosDisponibles($lunes, $this->tratamiento(30))));
        $this->assertSame('17:00', last($this->servicio->horariosDisponibles($lunes, $this->tratamiento(60, 'Quirúrgica'))));
        $this->assertSame('16:30', last($this->servicio->horariosDisponibles($lunes, $this->tratamiento(90))));
        $this->assertSame('16:00', last($this->servicio->horariosDisponibles($lunes, $this->tratamiento(120, 'Quirúrgica'))));
    }

    public function test_el_margen_sigue_contando_entre_una_cita_y_la_siguiente(): void
    {
        HorarioAtencion::where('dia_semana', 1)->update(['capacidad_simultanea' => 1]);
        $extraccion = $this->tratamiento(60, 'Quirúrgica');
        $paciente = \App\Models\Paciente::create(['nombre' => 'Ana', 'apellido' => 'Martínez', 'telefono' => '5512345678']);
        \App\Models\Cita::create(['id_paciente' => $paciente->id, 'id_tratamiento' => $extraccion->id, 'fecha_hora' => '2026-11-30 10:00:00',
            'duracion_estimada' => 60, 'estado' => 'agendada']);

        $horarios = $this->servicio->horariosDisponibles('2026-11-30', $this->tratamiento(30));
        // La extracción ocupa 10:00–11:00 más 15 min de limpieza: 11:00 no, 11:30 sí.
        $this->assertNotContains('10:30', $horarios);
        $this->assertNotContains('11:00', $horarios);
        $this->assertContains('11:30', $horarios);
    }

    public function test_al_guardar_se_rechaza_cualquier_hora_fuera_del_horario(): void
    {
        $limpieza = $this->tratamiento(60);
        $acepta = fn (string $f) => DB::transaction(fn () => $this->servicio->estaDisponible(Carbon::parse($f), $limpieza));

        $this->assertTrue($acepta('2026-11-30 10:00'));
        $this->assertTrue($acepta('2026-11-30 17:00'));   // termina justo al cierre
        $this->assertFalse($acepta('2026-11-30 22:00'));  // de noche
        $this->assertFalse($acepta('2026-11-30 06:00'));  // antes de abrir
        $this->assertFalse($acepta('2026-11-30 17:30'));  // terminaría 18:30
        $this->assertFalse($acepta('2026-11-30 10:15'));  // fuera de los saltos de 30 min
        $this->assertFalse($acepta('2026-11-29 10:00'));  // domingo cerrado

        // Lo mismo que ofrece la lista: cada horario ofrecido se puede guardar.
        foreach ($this->servicio->horariosDisponibles('2026-11-30', $limpieza) as $hora) {
            $this->assertTrue($acepta("2026-11-30 {$hora}"), $hora);
        }
    }
}

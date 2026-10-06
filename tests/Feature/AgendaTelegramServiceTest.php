<?php

namespace Tests\Feature;

use App\Models\HorarioAtencion;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Services\AgendaTelegramService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaTelegramServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guia_al_paciente_hasta_mostrar_horarios_reales(): void
    {
        $servicio = Servicio::create(['nombre' => 'Preventiva', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza Dental',
            'duracion_estimada' => 60,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento->id);

        $fecha = Carbon::tomorrow();
        while ($fecha->dayOfWeek === 0) {
            $fecha->addDay();
        }
        HorarioAtencion::where('dia_semana', $fecha->dayOfWeek)->update([
            'abierto' => true,
            'hora_apertura' => 8,
            'hora_cierre' => 18,
            'capacidad_simultanea' => 1,
        ]);

        $agenda = app(AgendaTelegramService::class);

        $this->assertStringContainsString('Preventiva', $agenda->responder(999, 'Quiero una cita'));
        $this->assertStringContainsString('Limpieza Dental', $agenda->responder(999, 'Preventiva'));
        $this->assertStringContainsString('AAAA-MM-DD', $agenda->responder(999, 'Limpieza Dental'));
        $this->assertStringContainsString('Horarios disponibles', $agenda->responder(999, $fecha->toDateString()));
    }
}

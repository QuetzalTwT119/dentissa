<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\HistorialClinico;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\DemoDatosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoDatosSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(6, 30));
        User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
    }

    public function test_no_corre_sin_habilitarlo(): void
    {
        config(['services.demo_presentacion.habilitada' => false]);

        $this->expectException(RuntimeException::class);
        $this->seed(DemoDatosSeeder::class);
    }

    public function test_llena_las_pantallas_sin_mandar_recordatorios_ni_dejar_avisos_pendientes(): void
    {
        config(['services.demo_presentacion.habilitada' => true]);

        $this->seed(DemoDatosSeeder::class);

        $this->assertSame(12, Paciente::where('email', 'like', 'demo.%@example.invalid')->count());
        $this->assertGreaterThan(30, Cita::count());
        $this->assertGreaterThan(0, HistorialClinico::count());
        $this->assertSame(0, Cita::whereNull('recordatorio_enviado_en')->count());
        $this->assertSame(0, NotificacionSistema::whereNull('leida_en')->orWhereNull('atendida_en')->count());
        // La mañana de hoy queda libre para agendar en vivo.
        $this->assertSame(0, Cita::whereBetween('fecha_hora', ['2026-11-25 08:00', '2026-11-25 11:00'])->count());

        // Correrlo otra vez no duplica nada.
        $citas = Cita::count();
        $this->seed(DemoDatosSeeder::class);
        $this->assertSame($citas, Cita::count());
    }
}

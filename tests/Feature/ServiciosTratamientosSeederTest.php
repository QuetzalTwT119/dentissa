<?php

namespace Tests\Feature;

use App\Models\Servicio;
use App\Models\Tratamiento;
use Database\Seeders\ServiciosTratamientosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiciosTratamientosSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_carga_los_tratamientos_reales_con_su_duracion(): void
    {
        $this->seed(ServiciosTratamientosSeeder::class);

        // Duraciones tomadas de la libreta de la clínica.
        $esperados = [
            'Valoración' => 30,
            'Limpieza Dental' => 60,
            'Retiro de Caries' => 60,
            'Extracción Dental' => 60,
            'Blanqueamiento Dental' => 90,
        ];

        $activos = Tratamiento::where('activo', true)->pluck('duracion_estimada', 'nombre')->all();
        ksort($activos);
        ksort($esperados);

        $this->assertSame($esperados, $activos);
        $this->assertSame(0, Tratamiento::whereNotNull('precio_referencia')->count(), 'Sin precios inventados.');
    }

    public function test_agrupa_los_tratamientos_en_servicios_activos(): void
    {
        $this->seed(ServiciosTratamientosSeeder::class);

        $general = Servicio::where('nombre', 'Odontología General')->firstOrFail();
        $estetica = Servicio::where('nombre', 'Odontología Estética')->firstOrFail();

        $this->assertSame(
            ['Valoración', 'Limpieza Dental', 'Retiro de Caries', 'Extracción Dental'],
            $general->tratamientos()->orderBy('servicios_tratamientos.orden')->pluck('nombre')->all(),
        );
        $this->assertSame(['Blanqueamiento Dental'], $estetica->tratamientos()->pluck('nombre')->all());

        // Ningún servicio activo queda vacío: el paciente no debe elegir algo sin tratamientos.
        $this->assertSame(0, Servicio::where('activo', true)->whereDoesntHave('tratamientos', fn ($q) => $q->where('activo', true))->count());
    }

    public function test_desactiva_el_catalogo_de_ejemplo_anterior_sin_borrarlo_y_respeta_lo_creado_por_la_clinica(): void
    {
        $implante = Tratamiento::create(['nombre' => 'Implante Dental', 'duracion_estimada' => 120, 'activo' => true]);
        $implantologia = Servicio::create(['nombre' => 'Implantología', 'orden' => 4, 'activo' => true]);
        $propio = Tratamiento::create(['nombre' => 'Guarda Oclusal', 'duracion_estimada' => 45, 'activo' => true]);

        $this->seed(ServiciosTratamientosSeeder::class);
        $this->seed(ServiciosTratamientosSeeder::class);

        $this->assertFalse($implante->fresh()->activo);
        $this->assertFalse($implantologia->fresh()->activo);
        $this->assertTrue($propio->fresh()->activo, 'Un tratamiento creado desde el panel no se toca.');
        $this->assertSame(1, Tratamiento::where('nombre', 'Valoración')->count(), 'Correr el seeder dos veces no duplica.');
    }
}

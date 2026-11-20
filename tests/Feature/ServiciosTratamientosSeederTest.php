<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
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
        // Decisión del 20/11/2026: sin precio no se publica, así que el catálogo trae
        // precios de demostración que la clínica debe confirmar.
        $this->assertSame(0, Tratamiento::where('activo', true)->where(fn ($q) => $q->whereNull('precio_referencia')->orWhere('precio_referencia', '<=', 0))->count(), 'Todo tratamiento activo tiene precio.');
        $this->assertSame('$3,500', Tratamiento::where('nombre', 'Blanqueamiento Dental')->first()->precioFormateado());
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

    public function test_puede_reordenar_tratamientos_ya_asignados_sin_fallar(): void
    {
        // Base con datos previos: Limpieza ya estaba asignada con otro orden.
        $general = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);
        $general->tratamientos()->attach($limpieza->id, ['orden' => 9]);

        $this->seed(ServiciosTratamientosSeeder::class);

        $this->assertSame(2, (int) $general->tratamientos()->whereKey($limpieza->id)->first()->pivot->orden);
    }

    public function test_la_categoria_se_conserva_en_los_datos_pero_el_panel_ya_no_la_pide(): void
    {
        $this->seed(ServiciosTratamientosSeeder::class);
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $valoracion = Tratamiento::where('nombre', 'Valoración')->firstOrFail();

        // Decisión del 20/11/2026: la categoría no se usaba en ningún lado; el dato se conserva.
        foreach (Tratamiento::where('activo', true)->pluck('categoria') as $categoria) {
            $this->assertContains($categoria, Tratamiento::CATEGORIAS);
        }

        $this->actingAs($admin)
            ->get(route('admin.tratamientos.edit', $valoracion))
            ->assertOk()
            ->assertDontSee('name="categoria"', false);
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

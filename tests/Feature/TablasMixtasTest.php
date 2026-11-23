<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tablas del panel en modo "Mixta": las cortas nunca se desplazan de lado
 * (en celular se apilan); las largas se desplazan lo mínimo con la primera
 * columna fija.
 */
class TablasMixtasTest extends TestCase
{
    use RefreshDatabase;

    /** Suma de anchos mínimos ≤ ~800 px: caben apiladas en un celular. */
    private const CORTAS = [
        'admin.pacientes.index',
        'admin.usuarios.index',
        'admin.servicios.index',
        'admin.testimonios.index',
        'admin.preguntas-frecuentes.index',
        'admin.casos.index',
        // admin.horarios.edit ya no es tabla: un renglón por día (HorariosAtencionTest).
    ];

    /** Columnas que no caben en un celular: se desplazan de lado. */
    private const LARGAS = [
        'admin.auditoria.index' => 'min-w-[850px]',
        'admin.mensajes.index' => 'min-w-[760px]',
        'admin.ofertas.index' => 'min-w-[760px]',
        'admin.tratamientos.index' => 'min-w-[700px]',
    ];

    private function admin(): User
    {
        return User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    public function test_las_tablas_cortas_se_apilan_y_nunca_se_desplazan_de_lado(): void
    {
        $admin = $this->admin();

        foreach (self::CORTAS as $ruta) {
            $html = $this->actingAs($admin)->get(route($ruta))->assertOk()->getContent();

            $this->assertStringContainsString('x-data="tablaApilable"', $html, $ruta);
            $this->assertStringContainsString('tabla-apilable', $html, $ruta);
            $this->assertStringNotContainsString('x-data="tablaLarga"', $html, $ruta);
            $this->assertStringNotContainsString('overflow-x-auto', $html, "{$ruta} no debe desplazarse de lado");
        }
    }

    public function test_las_tablas_largas_se_desplazan_con_la_primera_columna_fija(): void
    {
        $admin = $this->admin();

        foreach (self::LARGAS as $ruta => $anchoMinimo) {
            $this->actingAs($admin)->get(route($ruta))
                ->assertOk()
                ->assertSee('x-data="tablaLarga"', false)
                ->assertSee('class="tabla-larga tabla-panel"', false)
                ->assertSee('overflow-x-auto', false)
                ->assertSee($anchoMinimo, false)
                ->assertSee('Desliza para ver más columnas');
        }
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Decisiones del 20/11/2026:
 * - Arriba de cada lista: "Mostrando…/por página" a la izquierda y el botón
 *   "Nuevo …" DENTRO de la tarjeta, a la derecha. Abajo, centradas, las páginas
 *   (o "Página 1 de 1").
 * - Se quitó la cabecera de tarjeta que solo repetía el título de la página.
 * - El paciente sabe que el pago es en el consultorio el día de su cita.
 */
class BarraListasYPagoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => RolUsuario::Admin]);
    }

    public function test_la_barra_va_arriba_con_el_boton_de_crear_dentro(): void
    {
        Tratamiento::create(['nombre' => 'Valoración', 'duracion_estimada' => 30, 'precio_referencia' => 300, 'activo' => true]);

        $html = $this->actingAs($this->admin)->get(route('admin.tratamientos.index'))->assertOk()->getContent();

        $barra = strpos($html, 'data-paginacion="arriba"');
        $tabla = strpos($html, '<table');
        $boton = strpos($html, 'href="'.route('admin.tratamientos.create').'"');

        $this->assertNotFalse($barra);
        $this->assertLessThan($tabla, $barra, 'La barra va antes de la tabla.');
        $this->assertGreaterThan($barra, $boton, 'El botón "Nuevo tratamiento" va dentro de la barra.');
        $this->assertLessThan($tabla, $boton);
        // A la derecha: después de "Mostrando…" en la barra.
        $this->assertGreaterThan(strpos($html, 'Mostrando', $barra), $boton);
        $this->assertSame(1, substr_count($html, 'href="'.route('admin.tratamientos.create').'"'));
        $this->assertStringNotContainsString('Procedimientos clínicos', $html, 'Ya no está la cabecera que repetía el título.');
        // Las páginas van abajo, centradas: con una sola página dice "Página 1 de 1".
        $abajo = strpos($html, 'data-paginacion="abajo"');
        $this->assertGreaterThan($tabla, $abajo);
        $this->assertStringContainsString('Página 1 de 1', substr($html, $abajo, 600));
        $this->assertStringNotContainsString('Página 1 de 1', substr($html, $barra, $tabla - $barra));
    }

    public function test_con_la_lista_vacia_el_boton_de_crear_sigue_visible(): void
    {
        foreach (['tratamientos', 'servicios', 'ofertas', 'usuarios'] as $modulo) {
            if ($modulo === 'usuarios') {
                continue; // siempre existe al menos el admin
            }
            $this->actingAs($this->admin)
                ->get(route("admin.{$modulo}.index"))
                ->assertOk()
                ->assertSee('href="'.route("admin.{$modulo}.create").'"', false)
                ->assertSee('data-paginacion="arriba"', false);
        }
    }

    public function test_con_varias_paginas_abajo_van_los_botones_de_pagina_sin_repetir_el_selector(): void
    {
        foreach (range(1, 25) as $n) {
            Servicio::create(['nombre' => "Servicio {$n}", 'orden' => $n, 'activo' => true]);
        }

        $html = $this->actingAs($this->admin)->get(route('admin.servicios.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-paginacion="abajo"', $html);
        $abajo = substr($html, strpos($html, 'data-paginacion="abajo"'));
        $this->assertStringNotContainsString('por página', substr($abajo, 0, 3000), 'Abajo no se repite el selector.');
    }

    public function test_el_paciente_sabe_que_el_pago_es_en_el_consultorio(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $servicio->tratamientos()->attach(
            Tratamiento::create(['nombre' => 'Valoración', 'duracion_estimada' => 30, 'precio_referencia' => 300, 'activo' => true])->id,
            ['orden' => 1],
        );

        // Al elegir tratamiento y en el resumen.
        $this->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('El pago se realiza en el consultorio el día de tu cita.')
            ->assertSee('Se paga en el consultorio');

        // En la confirmación final.
        $this->withSession([
            'success' => 'Cita registrada',
            'cita_resumen' => ['tratamiento' => 'Valoración', 'fecha' => 'lunes 23 de noviembre de 2026', 'hora' => '10:00', 'precio' => '$300'],
        ])->get(route('citas.publica.create'))
            ->assertOk()
            ->assertSee('$300')
            ->assertSee('Se paga en el consultorio el día de tu cita.');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\HorarioAtencion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisión del 20/11/2026: el pie de la portada decía "Horarios por confirmar"
 * aunque la clínica ya los había configurado, y la pantalla de edición era una
 * tabla alta con el botón al final.
 */
class HorariosAtencionTest extends TestCase
{
    use RefreshDatabase;

    /** Los 7 días tal como vienen de la migración (domingo cerrado). */
    private function dias(array $cambios = []): array
    {
        return HorarioAtencion::orderBy('dia_semana')->get()->map(fn ($h, $i) => array_merge([
            'dia_semana' => $h->dia_semana,
            'abierto' => $h->abierto ? '1' : null,
            'hora_apertura' => $h->hora_apertura,
            'hora_cierre' => $h->hora_cierre,
            'capacidad_simultanea' => $h->capacidad_simultanea,
        ], $cambios[$i] ?? []))->map(fn ($d) => array_filter($d, fn ($v) => $v !== null))->all();
    }

    public function test_el_pie_de_la_portada_muestra_el_horario_configurado(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Horarios por confirmar')
            ->assertSee('Lunes a viernes de 8:00 a 18:00 hrs')
            ->assertSee('Sábado de 9:00 a 14:00 hrs');

        HorarioAtencion::where('dia_semana', 6)->update(['abierto' => false]);

        $this->get('/')->assertOk()->assertDontSee('Sábado de 9:00');
    }

    /** 22/11/2026: Horarios es un bloque de Configuración, con su botón al final del bloque. */
    public function test_la_pantalla_es_compacta_con_guardar_e_interruptor(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)->get(route('admin.configuracion'))
            ->assertOk()
            ->assertSee('id="form-horarios"', false)
            ->assertSee('Guardar horarios')
            ->assertSee('role="switch"', false)
            ->assertSee('Citas a la vez')
            ->assertDontSee('Capacidad simultánea')
            ->assertDontSee('<table', false);
    }

    public function test_no_guarda_un_dia_abierto_que_cierra_antes_de_abrir(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        // Índice 1 = lunes: abre a las 18 y "cierra" a las 8.
        $this->actingAs($admin)->from(route('admin.configuracion'))
            ->put(route('admin.horarios.update'), ['dias' => $this->dias([1 => ['hora_apertura' => 18, 'hora_cierre' => 8]])])
            ->assertSessionHasErrors('dias.1.hora_cierre');

        $this->assertSame(8, HorarioAtencion::where('dia_semana', 1)->value('hora_apertura'));

        // Un día cerrado puede quedarse con cualquier hora (no se usa).
        $this->actingAs($admin)
            ->put(route('admin.horarios.update'), ['dias' => $this->dias([0 => ['hora_apertura' => 18, 'hora_cierre' => 8]])])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.configuracion').'#horarios');
    }
}

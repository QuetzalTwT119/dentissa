<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\LogAuditoria;
use App\Models\Oferta;
use App\Models\Paciente;
use App\Models\Pago;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pagos e ingresos (24/11/2026): registrar lo cobrado y verlo por periodo.
 */
class PagosTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    private Paciente $paciente;

    private Tratamiento $limpieza;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-24 12:00:00');
        // Oculto por omisión (24/11/2026); aquí se prueba encendido.
        config(['dentissa.modulos.pagos' => true]);
        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $this->paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5511112222']);
        $this->limpieza = Tratamiento::create(['nombre' => 'Limpieza', 'duracion_estimada' => 30, 'activo' => true, 'precio_referencia' => 600]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cita(string $estado = 'completada', array $extra = []): Cita
    {
        return Cita::create($extra + ['id_paciente' => $this->paciente->id, 'id_tratamiento' => $this->limpieza->id, 'fecha_hora' => '2026-11-24 10:00', 'duracion_estimada' => 30, 'estado' => $estado]);
    }

    private function pagar(array $datos = [])
    {
        return $this->actingAs($this->doctora)->post(route('admin.pagos.store'), $datos + [
            'id_paciente' => $this->paciente->id, 'monto' => '600', 'metodo' => 'efectivo', 'fecha' => '2026-11-24',
        ]);
    }

    public function test_desde_la_agenda_registra_el_pago_de_una_cita_completada(): void
    {
        $cita = $this->cita();

        $this->actingAs($this->doctora)->get(route('citas.index', ['estado' => 'completada', 'fecha' => '2026-11-24']))->assertOk()
            ->assertSee(route('admin.pagos.create', ['paciente' => $this->paciente->id, 'cita' => $cita->id]));

        $this->actingAs($this->doctora)->get(route('admin.pagos.create', ['paciente' => $this->paciente->id, 'cita' => $cita->id]))->assertOk()
            ->assertSee('value="600"', false)
            ->assertSee('Precio estimado de la cita: $600');

        $this->pagar(['id_cita' => $cita->id])
            ->assertRedirect(route('admin.pacientes.historial.index', $this->paciente))
            ->assertSessionHas('success', 'Pago de $600 registrado.');

        $pago = Pago::sole();
        $this->assertSame($cita->id, $pago->id_cita);
        $this->assertSame($this->doctora->id, $pago->registrado_por);
        $this->assertTrue(LogAuditoria::where('accion', 'pago.registrado')->where('registro_id', $pago->id)->exists());

        // Ya pagada: la Agenda deja de ofrecerlo.
        $this->actingAs($this->doctora)->get(route('citas.index', ['estado' => 'completada', 'fecha' => '2026-11-24']))
            ->assertDontSee('data-registrar-pago', false);
    }

    public function test_el_expediente_muestra_lo_pagado_y_lo_que_falta(): void
    {
        $this->cita();
        $oferta = Oferta::create(['titulo' => 'Promo', 'estado' => 'activa', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 50, 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-12-31']);
        $conOferta = $this->cita('completada', ['id_oferta' => $oferta->id]);
        $this->assertSame(300.0, $conOferta->precioEstimado());

        $this->pagar(['monto' => '500']);

        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.index', $this->paciente))->assertOk()
            ->assertSee('data-pagos-paciente', false)
            ->assertSee('Pagado: <strong class="text-ink">$500</strong>', false)
            ->assertSee('$400'); // 600 + 300 − 500
    }

    public function test_valida_monto_fecha_metodo_y_que_la_cita_sea_del_paciente(): void
    {
        $otro = Paciente::create(['nombre' => 'Beto', 'apellido' => 'Paz', 'telefono' => '5533334444']);
        $ajena = Cita::create(['id_paciente' => $otro->id, 'id_tratamiento' => $this->limpieza->id, 'fecha_hora' => '2026-11-24 11:00', 'duracion_estimada' => 30, 'estado' => 'completada']);

        $this->pagar(['monto' => '0'])->assertSessionHasErrors('monto');
        $this->pagar(['fecha' => '2026-11-25'])->assertSessionHasErrors('fecha');
        $this->pagar(['metodo' => 'cripto'])->assertSessionHasErrors('metodo');
        $this->pagar(['id_cita' => $ajena->id])->assertSessionHasErrors('id_cita');
        $this->assertSame(0, Pago::count());
    }

    public function test_ingresos_por_periodo_y_forma_de_pago(): void
    {
        $this->pagar(['monto' => '600', 'metodo' => 'efectivo', 'fecha' => '2026-11-03']);
        $this->pagar(['monto' => '1200.50', 'metodo' => 'tarjeta', 'fecha' => '2026-11-20']);
        $this->pagar(['monto' => '999', 'metodo' => 'transferencia', 'fecha' => '2026-10-31']); // mes anterior

        // Mes en curso: solo los dos de noviembre (el aviso verde del último pago no cuenta).
        $html = $this->actingAs($this->doctora)->get(route('admin.pagos.index'))->assertOk()
            ->assertSee('<p class="mt-1 text-title-2 text-ink" data-total-ingresos>$1,800.50</p>', false)
            ->assertSee('2 pagos')
            ->getContent();
        $this->assertSame(2, substr_count($html, 'data-pago>'));

        $this->actingAs($this->doctora)->get(route('admin.pagos.index', ['desde' => '2026-10-01', 'hasta' => '2026-11-30', 'metodo' => 'transferencia']))
            ->assertSee('data-total-ingresos>$999</p>', false);
    }

    public function test_eliminar_un_pago_queda_registrado(): void
    {
        $this->pagar();
        $pago = Pago::sole();

        $this->actingAs($this->doctora)->from(route('admin.pagos.index'))->delete(route('admin.pagos.destroy', $pago))->assertRedirect(route('admin.pagos.index'));
        $this->assertSame(0, Pago::count());
        $this->assertTrue(LogAuditoria::where('accion', 'pago.eliminado')->exists());
    }

    public function test_apagado_no_aparece_en_ningun_lado(): void
    {
        config(['dentissa.modulos.pagos' => false]);
        $this->cita();

        $this->actingAs($this->doctora)->get(route('dashboard'))->assertDontSee(route('admin.pagos.index'), false);
        $this->actingAs($this->doctora)->get(route('admin.pacientes.historial.index', $this->paciente))->assertOk()->assertDontSee('data-pagos-paciente', false);
        $this->actingAs($this->doctora)->get(route('citas.index', ['estado' => 'completada', 'fecha' => '2026-11-24']))->assertDontSee('data-registrar-pago', false);
        $this->actingAs($this->doctora)->get(route('admin.pagos.index'))->assertForbidden();
        $this->assertFalse($this->doctora->puede('pagos'));
    }

    public function test_menu_y_permisos(): void
    {
        $this->actingAs($this->doctora)->get(route('dashboard'))->assertSee(route('admin.pagos.index'), false);

        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor]);
        $this->actingAs($doctor)->get(route('admin.pagos.index'))->assertOk();

        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $this->actingAs($asistente)->get(route('admin.pagos.index'))->assertForbidden();
        $this->actingAs($asistente)->post(route('admin.pagos.store'), ['id_paciente' => $this->paciente->id, 'monto' => 1, 'metodo' => 'efectivo', 'fecha' => '2026-11-24'])->assertForbidden();

        // En la Agenda, la asistente no ve "Registrar pago".
        $this->cita();
        $this->actingAs($asistente)->get(route('citas.index', ['estado' => 'completada', 'fecha' => '2026-11-24']))->assertOk()->assertDontSee('data-registrar-pago', false);
    }
}

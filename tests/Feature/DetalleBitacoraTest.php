<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Models\User;
use App\Support\DetalleBitacora;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 22/11/2026 (encontrado por el usuario): "Ver cambios" mostraba nombres
 * internos ("Tratamiento ids", "Tipo valor"), valores crudos ("en_curso",
 * "[1]", "2027-01-31T00:00:00-06:00", "22.00"), campos que no cambiaron y se
 * abría dentro de la celda, estirando la fila.
 */
class DetalleBitacoraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DetalleBitacora::reiniciar();
    }

    private function registro(string $tabla, ?array $antes, ?array $despues): LogAuditoria
    {
        return new LogAuditoria(['tabla' => $tabla, 'accion' => 'x', 'datos_anteriores' => $antes, 'datos_nuevos' => $despues]);
    }

    public function test_solo_lo_que_cambio_y_en_palabras(): void
    {
        // Captura 5: la cita pasó de confirmada a en curso; la fecha no cambió.
        $filas = DetalleBitacora::filas($this->registro('citas',
            ['estado' => 'confirmada', 'fecha_hora' => '2026-10-21T10:30:00-06:00'],
            ['estado' => 'en_curso', 'fecha_hora' => '2026-10-21T10:30:00-06:00']));

        $this->assertSame([['campo' => 'Estado', 'antes' => 'Confirmada', 'despues' => 'En curso']], $filas);
    }

    public function test_ids_se_ven_como_nombres(): void
    {
        $limpieza = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 30, 'activo' => true]);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Martínez', 'telefono' => '5512345678']);

        // Captura 1: "Tratamiento ids [] → [1]".
        $this->assertSame([['campo' => 'Tratamientos', 'antes' => 'Ninguno', 'despues' => 'Limpieza Dental']],
            DetalleBitacora::filas($this->registro('servicios', ['tratamiento_ids' => []], ['tratamiento_ids' => [$limpieza->id]])));

        // Captura 4: consulta clínica nueva.
        $filas = collect(DetalleBitacora::filas($this->registro('historial_clinico', null,
            ['id_cita' => null, 'id_paciente' => $paciente->id, 'tipo_consulta' => 'primera_vez', 'fecha_consulta' => '2026-10-16'])))
            ->pluck('despues', 'campo')->all();
        $this->assertSame(['Cita' => '—', 'Paciente' => 'Ana Martínez', 'Tipo de consulta' => 'Primera vez', 'Fecha de la consulta' => '16 oct. 2026'], $filas);

        // Un registro borrado después no rompe la vista.
        $this->assertSame('#999 (ya no existe)', DetalleBitacora::valor('id_tratamiento', 999));
    }

    public function test_oferta_nueva_en_palabras_y_sin_campos_internos(): void
    {
        $extraccion = Tratamiento::create(['nombre' => 'Extracción Dental', 'duracion_estimada' => 60, 'activo' => true]);

        // Captura 3.
        $filas = collect(DetalleBitacora::filas($this->registro('ofertas', null, [
            'tipo' => 'descuento', 'estado' => 'pendiente', 'imagen' => null, 'titulo' => 'Oferta que se oculta',
            'fecha_fin' => '2027-01-31T00:00:00-06:00', 'cupo_total' => 0, 'tipo_valor' => 'porcentaje',
            'fecha_inicio' => '2026-11-22T00:00:00-06:00', 'id_tratamiento' => $extraccion->id,
            'precio_especial' => null, 'valor_descuento' => '22.00',
        ])))->pluck('despues', 'campo')->all();

        $this->assertSame('Descuento', $filas['Tipo']);
        $this->assertSame('Pendiente', $filas['Estado']);
        $this->assertSame('Sin foto', $filas['Foto']);
        $this->assertSame('31 ene. 2027', $filas['Termina']);
        $this->assertSame('Sin límite', $filas['Lugares']);
        $this->assertSame('Porcentaje', $filas['Tipo de descuento']);
        $this->assertSame('Extracción Dental', $filas['Tratamiento']);
        $this->assertSame('22 %', $filas['Descuento']);
        // Descuento de cantidad fija (encontrado al revisar las 12 páginas).
        $this->assertSame('Cantidad fija', DetalleBitacora::valor('tipo_valor', 'monto_fijo'));
        $this->assertSame('$150', DetalleBitacora::valor('valor_descuento', '150.00', ['tipo_valor' => 'monto_fijo']));

        // Captura 2: "Icono tooth", "Orden 8", "Descripcion".
        $filas = collect(DetalleBitacora::filas($this->registro('servicios', null,
            ['icono' => 'tooth', 'orden' => 8, 'activo' => true, 'imagen' => null, 'nombre' => 'prueba', 'descripcion' => 'a'])))
            ->pluck('despues', 'campo')->all();
        $this->assertSame(['Orden en la portada' => '8', 'Disponible' => 'Sí', 'Foto' => 'Sin foto', 'Nombre' => 'prueba', 'Descripción' => 'a'], $filas);

        // Captura 4 (abajo): "Campos modificados ["nombre"]".
        $this->assertSame([['campo' => 'Campos que cambiaron', 'antes' => null, 'despues' => 'Nombre, Teléfono']],
            DetalleBitacora::filas($this->registro('pacientes', null, ['campos_modificados' => ['nombre', 'telefono']])));
    }

    /** 22/11/2026 (decisión del usuario): el detalle se abre en una ventana, fuera de la tabla. */
    public function test_el_detalle_se_abre_en_una_ventana(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        LogAuditoria::query()->create([
            'id_usuario' => $admin->id, 'accion' => 'cita.iniciada', 'tabla' => 'citas', 'registro_id' => 2, 'fecha' => now(),
            'datos_anteriores' => ['estado' => 'confirmada'], 'datos_nuevos' => ['estado' => 'en_curso'],
        ]);

        $this->actingAs($admin)->get(route('admin.auditoria.index'))->assertOk()
            ->assertSee('data-ver-cambios', false)
            ->assertSee('data-ventana-cambios', false)
            ->assertSee('role="dialog" aria-modal="true"', false)
            ->assertSee("\$dispatch('open-modal', 'cambios-", false)
            ->assertDontSee('data-detalle-cambios', false)
            ->assertSee('Confirmada')
            ->assertSee('En curso')
            ->assertDontSee('en_curso')
            ->assertDontSee('aria-label="Ver cambios detallados del evento"', false)
            ->assertDontSee('font-mono text-ink font-semibold', false);
    }
}

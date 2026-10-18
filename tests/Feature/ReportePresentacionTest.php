<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\TestCase;

class ReportePresentacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporte_muestra_metricas_con_nombres_claros_y_singular_correcto(): void
    {
        $this->mostrarReporteConConsultas(1)
            ->assertSee('Citas completadas por hora')
            ->assertSee('Descargar Excel')
            ->assertSee('Total histórico registrado')
            ->assertSee('Catálogo actual:')
            ->assertSee('Historial clínico (1 consulta)')
            ->assertDontSee('Comparativa Gerín');
    }

    public function test_reporte_usa_plural_cuando_hay_varias_consultas(): void
    {
        $this->mostrarReporteConConsultas(2)
            ->assertSee('Historial clínico (2 consultas)');
    }

    public function test_filtro_rechaza_fechas_futuras_y_acepta_hoy(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $manana = now()->addDay()->toDateString();

        $this->actingAs($admin)
            ->get(route('admin.reportes.index', [
                'fecha_inicio' => $manana,
                'fecha_fin' => $manana,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['fecha_inicio', 'fecha_fin']);

        $hoy = now()->toDateString();
        $reporte = $this->datosReporte(0);
        $reporte['rango'] = ['fecha_inicio' => $hoy, 'fecha_fin' => $hoy];
        $servicio = Mockery::mock(ReporteService::class);
        $servicio->shouldReceive('generar')->once()->with($hoy, $hoy)->andReturn($reporte);
        $this->instance(ReporteService::class, $servicio);

        $this->actingAs($admin)
            ->get(route('admin.reportes.index', [
                'fecha_inicio' => $hoy,
                'fecha_fin' => $hoy,
            ]))
            ->assertOk()
            ->assertSee('max="'.$hoy.'"', false);
    }

    public function test_exportacion_excel_descarga_un_libro_xlsx_legible_con_acentos(): void
    {
        $reporte = $this->datosReporte(1);
        $servicio = Mockery::mock(ReporteService::class);
        $servicio->shouldReceive('generar')->once()->andReturn($reporte);
        $this->instance(ReporteService::class, $servicio);

        $respuesta = $this->actingAs(User::factory()->create(['rol' => RolUsuario::Admin]))
            ->get(route('admin.reportes.excel'));
        $contenido = $respuesta->streamedContent();

        $respuesta->assertOk()->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringStartsWith("PK\x03\x04", $contenido);

        $rutaTemporal = tempnam(sys_get_temp_dir(), 'dentissa-reporte-');

        try {
            file_put_contents($rutaTemporal, $contenido);
            $libro = (new Xlsx)->load($rutaTemporal);
            $hoja = $libro->getActiveSheet();

            $this->assertSame('Dentissa · Reporte administrativo', $hoja->getCell('A1')->getValue());
            $this->assertSame('Indicadores clave', $hoja->getCell('A4')->getValue());
            $this->assertSame('Tratamientos más solicitados', $hoja->getCell('A17')->getValue());
            $this->assertSame('Historial clínico (1 consulta)', $hoja->getCell('A21')->getValue());
            $this->assertSame(0, $hoja->getCell('B5')->getValue());
            $etiquetas = $hoja->rangeToArray('A1:A'.$hoja->getHighestRow(), null, true, false);
            $this->assertContains(['Citas atendidas'], $etiquetas);
            $this->assertContains(['Total histórico registrado'], $etiquetas);
            $this->assertContains(['Servicios activos en el catálogo actual'], $etiquetas);
            $libro->disconnectWorksheets();
        } finally {
            @unlink($rutaTemporal);
        }
    }

    private function mostrarReporteConConsultas(int $totalConsultas)
    {
        $reporte = $this->datosReporte($totalConsultas);

        $servicio = Mockery::mock(ReporteService::class);
        $servicio->shouldReceive('generar')->once()->andReturn($reporte);
        $this->instance(ReporteService::class, $servicio);

        return $this->actingAs(User::factory()->create(['rol' => RolUsuario::Admin]))
            ->get(route('admin.reportes.index'));
    }

    private function datosReporte(int $totalConsultas): array
    {
        return [
            'rango' => ['fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-16'],
            'pacientes' => ['total' => 1, 'nuevos' => 1],
            'citas' => ['total' => 0, 'por_estado' => [], 'tasa_inasistencia' => 0],
            'catalogo' => [
                'tratamientos_mas_solicitados' => [],
                'servicios_activos' => 7,
                'tratamientos_activos' => 8,
            ],
            'historial' => [
                'total' => $totalConsultas,
                'por_tipo' => ['primera_vez' => $totalConsultas],
            ],
            'eficiencia' => ['citas_completadas' => 0, 'eficiencia' => 0, 'citas_por_hora' => 0],
            'ocupacion' => [
                'capacidad_total' => 0,
                'citas_atendidas' => 0,
                'tasa_ocupacion' => 0,
            ],
            'satisfaccion' => null,
        ];
    }
}

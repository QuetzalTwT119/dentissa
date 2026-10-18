<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteExcelService
{
    public function crear(array $reporte): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Dentissa')
            ->setTitle('Reporte administrativo')
            ->setSubject('Reporte Dentissa');

        $hoja = $spreadsheet->getActiveSheet();
        $hoja->setTitle('Reporte Dentissa');
        $hoja->setShowGridlines(false);

        $filas = [
            ['Dentissa · Reporte administrativo'],
            ['Período', $reporte['rango']['fecha_inicio'].' al '.$reporte['rango']['fecha_fin']],
            [],
        ];
        $secciones = [];
        $agregarSeccion = function (string $titulo) use (&$filas, &$secciones): void {
            $filas[] = [$titulo];
            $secciones[] = count($filas);
        };

        $agregarSeccion('Indicadores clave');
        $filas[] = ['Tasa de ocupación (%)', $reporte['ocupacion']['tasa_ocupacion']];
        $filas[] = ['Tasa de inasistencia (%)', $reporte['citas']['tasa_inasistencia']];
        $filas[] = ['Eficiencia (citas por bloque de 1.5 h)', $reporte['eficiencia']['eficiencia']];
        $filas[] = ['Citas completadas por hora', $reporte['eficiencia']['citas_por_hora']];
        $filas[] = ['Satisfacción promedio', $reporte['satisfaccion'] ?? 'Sin datos'];
        $filas[] = [];

        $agregarSeccion('Pacientes');
        $filas[] = ['Total histórico registrado', $reporte['pacientes']['total']];
        $filas[] = ['Nuevos en el período', $reporte['pacientes']['nuevos']];
        $filas[] = [];

        $agregarSeccion('Citas por estado ('.$reporte['citas']['total'].' en el período)');
        $etiquetasEstado = [
            'agendada' => 'Agendada',
            'confirmada' => 'Confirmada',
            'en_curso' => 'En curso',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada',
            'no_asistio' => 'No asistió',
        ];
        foreach ($reporte['citas']['por_estado'] as $estado => $total) {
            $filas[] = [$etiquetasEstado[$estado] ?? $estado, $total];
        }
        $filas[] = [];

        $agregarSeccion('Tratamientos más solicitados');
        foreach ($reporte['catalogo']['tratamientos_mas_solicitados'] as $tratamiento) {
            $filas[] = [$tratamiento['nombre'], $tratamiento['total']];
        }
        $filas[] = ['Servicios activos en el catálogo actual', $reporte['catalogo']['servicios_activos']];
        $filas[] = ['Tratamientos activos en el catálogo actual', $reporte['catalogo']['tratamientos_activos']];
        $filas[] = [];

        $totalConsultas = $reporte['historial']['total'];
        $etiquetaConsultas = $totalConsultas === 1 ? 'consulta' : 'consultas';
        $agregarSeccion("Historial clínico ({$totalConsultas} {$etiquetaConsultas})");
        $etiquetasConsulta = [
            'primera_vez' => 'Primera vez',
            'seguimiento' => 'Seguimiento',
            'urgencia' => 'Urgencia',
            'control' => 'Control',
        ];
        foreach ($reporte['historial']['por_tipo'] as $tipo => $total) {
            $filas[] = [$etiquetasConsulta[$tipo] ?? $tipo, $total];
        }

        $filas[] = [];
        $agregarSeccion('Detalle de eficiencia y ocupación');
        $filas[] = ['Citas completadas', $reporte['eficiencia']['citas_completadas']];
        $filas[] = ['Eficiencia (citas por bloque de 1.5 h)', $reporte['eficiencia']['eficiencia']];
        $filas[] = ['Citas completadas por hora', $reporte['eficiencia']['citas_por_hora']];
        $filas[] = ['Capacidad total', $reporte['ocupacion']['capacidad_total']];
        $filas[] = ['Citas atendidas', $reporte['ocupacion']['citas_atendidas']];
        $filas[] = ['Tasa de ocupación (%)', $reporte['ocupacion']['tasa_ocupacion']];

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 1;

            foreach (['A', 'B'] as $indiceColumna => $columna) {
                $valor = $fila[$indiceColumna] ?? null;

                if ($valor === null) {
                    continue;
                }

                $tipo = is_int($valor) || is_float($valor) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING;
                $hoja->setCellValueExplicit($columna.$numeroFila, $valor, $tipo);
            }
        }

        $hoja->mergeCells('A1:B1');
        $hoja->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 16],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D94B78']],
        ]);
        $hoja->getStyle('A2:B2')->getFont()->setItalic(true)->getColor()->setRGB('64748B');

        foreach ($secciones as $fila) {
            $hoja->getStyle("A{$fila}:B{$fila}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'B4235A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE7EF']],
            ]);
        }

        $hoja->getColumnDimension('A')->setWidth(44);
        $hoja->getColumnDimension('B')->setWidth(24);
        $hoja->getStyle('A1:B'.count($filas))->getAlignment()->setVertical('center')->setWrapText(true);
        $hoja->getStyle('B1:B'.count($filas))->getAlignment()->setHorizontal('right');
        $hoja->getRowDimension(1)->setRowHeight(30);
        $hoja->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
        $hoja->getPageSetup()->setOrientation('portrait');

        return $spreadsheet;
    }
}

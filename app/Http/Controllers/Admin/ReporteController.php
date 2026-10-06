<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function index(Request $request, ReporteService $reportes): View
    {
        [$fechaInicio, $fechaFin] = $this->rango($request);

        return view('admin.reportes.index', [
            'reporte' => $reportes->generar($fechaInicio, $fechaFin),
        ]);
    }

    public function pdf(Request $request, ReporteService $reportes): HttpResponse
    {
        [$fechaInicio, $fechaFin] = $this->rango($request);

        $pdf = Pdf::loadView('pdf.reporte', [
            'reporte' => $reportes->generar($fechaInicio, $fechaFin),
        ]);

        return $pdf->download("reporte-dentissa-{$fechaInicio}-a-{$fechaFin}.pdf");
    }

    public function csv(Request $request, ReporteService $reportes): StreamedResponse
    {
        [$fechaInicio, $fechaFin] = $this->rango($request);
        $reporte = $reportes->generar($fechaInicio, $fechaFin);

        return Response::streamDownload(function () use ($reporte) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Reporte Dentissa', $reporte['rango']['fecha_inicio'].' a '.$reporte['rango']['fecha_fin']]);
            fputcsv($out, []);

            fputcsv($out, ['Pacientes']);
            fputcsv($out, ['Total registrados', $reporte['pacientes']['total']]);
            fputcsv($out, ['Nuevos en el período', $reporte['pacientes']['nuevos']]);
            fputcsv($out, []);

            fputcsv($out, ['Citas por estado']);
            foreach ($reporte['citas']['por_estado'] as $estado => $total) {
                fputcsv($out, [$estado, $total]);
            }
            fputcsv($out, ['Tasa de inasistencia (%)', $reporte['citas']['tasa_inasistencia']]);
            fputcsv($out, []);

            fputcsv($out, ['Tratamientos más solicitados']);
            foreach ($reporte['catalogo']['tratamientos_mas_solicitados'] as $fila) {
                fputcsv($out, [$fila['nombre'], $fila['total']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Consultas de historial clínico por tipo']);
            foreach ($reporte['historial']['por_tipo'] as $tipo => $total) {
                fputcsv($out, [$tipo, $total]);
            }
            fputcsv($out, []);

            fputcsv($out, ['Eficiencia']);
            fputcsv($out, ['Citas completadas', $reporte['eficiencia']['citas_completadas']]);
            fputcsv($out, ['Eficiencia (citas/bloque de 1.5h)', $reporte['eficiencia']['eficiencia']]);
            fputcsv($out, ['Comparativa Gerín (citas/hora ÷ 1)', $reporte['eficiencia']['comparativa_gerin']]);
            fputcsv($out, []);

            fputcsv($out, ['Ocupación']);
            fputcsv($out, ['Capacidad total', $reporte['ocupacion']['capacidad_total']]);
            fputcsv($out, ['Citas atendidas', $reporte['ocupacion']['citas_atendidas']]);
            fputcsv($out, ['Tasa de ocupación (%)', $reporte['ocupacion']['tasa_ocupacion']]);
            fputcsv($out, []);

            fputcsv($out, ['Satisfacción promedio', $reporte['satisfaccion'] ?? 'Sin datos']);

            fclose($out);
        }, "reporte-dentissa-{$fechaInicio}-a-{$fechaFin}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function rango(Request $request): array
    {
        $validated = $request->validate([
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date'],
        ]);

        $fechaInicio = $validated['fecha_inicio'] ?? Carbon::now()->startOfMonth()->toDateString();
        $fechaFin = $validated['fecha_fin'] ?? Carbon::now()->toDateString();

        if ($fechaInicio > $fechaFin) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La fecha "Hasta" debe ser igual o posterior a la fecha "Desde".',
            ]);
        }

        return [$fechaInicio, $fechaFin];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReporteExcelService;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    public function excel(Request $request, ReporteService $reportes, ReporteExcelService $excel): StreamedResponse
    {
        [$fechaInicio, $fechaFin] = $this->rango($request);
        $reporte = $reportes->generar($fechaInicio, $fechaFin);
        $spreadsheet = $excel->crear($reporte);

        return response()->streamDownload(function () use ($spreadsheet) {
            try {
                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, "reporte-dentissa-{$fechaInicio}-a-{$fechaFin}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function rango(Request $request): array
    {
        $validated = $request->validate([
            'fecha_inicio' => ['nullable', 'date', 'before_or_equal:'.Carbon::now()->toDateString()],
            'fecha_fin' => ['nullable', 'date', 'before_or_equal:'.Carbon::now()->toDateString()],
        ], [
            'fecha_inicio.before_or_equal' => 'La fecha "Desde" no puede ser futura.',
            'fecha_fin.before_or_equal' => 'La fecha "Hasta" no puede ser futura.',
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

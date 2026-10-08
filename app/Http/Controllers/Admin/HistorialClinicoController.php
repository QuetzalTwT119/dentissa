<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoImagen;
use App\Http\Controllers\Controller;
use App\Models\HistorialClinico;
use App\Models\Imagen;
use App\Models\Paciente;
use App\Services\AuditoriaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Redirect;

class HistorialClinicoController extends Controller
{
    public function index(Paciente $paciente): View
    {
        return view('admin.historial.index', [
            'paciente' => $paciente,
            'consultas' => $paciente->historialClinico()
                ->with(['doctor:id,nombre,apellido', 'cita', 'imagenes'])
                ->orderByDesc('fecha_consulta')
                ->get(),
        ]);
    }

    public function create(Paciente $paciente): View
    {
        return view('admin.historial.create', [
            'paciente' => $paciente,
            'citas' => $paciente->citas()->orderByDesc('fecha_hora')->get(['id', 'fecha_hora']),
        ]);
    }

    public function store(Request $request, Paciente $paciente, AuditoriaService $auditoria): RedirectResponse
    {
        $validated = $this->validated($request, $paciente);

        $historial = $paciente->historialClinico()->create([
            ...$validated,
            'id_doctor' => $request->user()->id,
        ]);

        $auditoria->registrar('historial.creado', $historial, datosNuevos: $this->datosAuditoria($historial), request: $request);

        return Redirect::route('admin.pacientes.historial.index', $paciente);
    }

    public function edit(Paciente $paciente, HistorialClinico $historial): View
    {
        $this->verificarPertenece($paciente, $historial);

        return view('admin.historial.edit', [
            'paciente' => $paciente,
            'historial' => $historial->load('imagenes'),
            'citas' => $paciente->citas()->orderByDesc('fecha_hora')->get(['id', 'fecha_hora']),
        ]);
    }

    public function update(Request $request, Paciente $paciente, HistorialClinico $historial, AuditoriaService $auditoria): RedirectResponse
    {
        $this->verificarPertenece($paciente, $historial);

        $datosAnteriores = $this->datosAuditoria($historial);

        $historial->update($this->validated($request, $paciente));
        $auditoria->registrar('historial.actualizado', $historial, $datosAnteriores, $this->datosAuditoria($historial), $request);

        return Redirect::route('admin.pacientes.historial.index', $paciente);
    }

    public function destroy(Request $request, Paciente $paciente, HistorialClinico $historial, AuditoriaService $auditoria): RedirectResponse
    {
        $this->verificarPertenece($paciente, $historial);

        $datosAnteriores = $this->datosAuditoria($historial);

        $historial->delete();
        $auditoria->registrar('historial.eliminado', $historial, $datosAnteriores, request: $request);

        return Redirect::route('admin.pacientes.historial.index', $paciente);
    }

    public function storeImagen(Request $request, Paciente $paciente, HistorialClinico $historial, AuditoriaService $auditoria): RedirectResponse
    {
        $this->verificarPertenece($paciente, $historial);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $ruta = $request->file('archivo')->store('historial', 'local');

        $imagen = Imagen::create([
            'titulo' => $request->file('archivo')->getClientOriginalName(),
            'url' => $ruta,
            'tipo' => TipoImagen::HistorialClinico,
            'referencia_id' => $historial->id,
        ]);

        $auditoria->registrar('historial.imagen_agregada', $imagen, datosNuevos: [
            'id_historial' => $historial->id,
            'tipo' => TipoImagen::HistorialClinico->value,
        ], request: $request);

        return Redirect::route('admin.pacientes.historial.edit', [$paciente, $historial]);
    }

    public function destroyImagen(Request $request, Paciente $paciente, HistorialClinico $historial, Imagen $imagen, AuditoriaService $auditoria): RedirectResponse
    {
        $this->verificarPertenece($paciente, $historial);
        abort_unless($imagen->referencia_id === $historial->id && $imagen->tipo === TipoImagen::HistorialClinico, 404);

        $datosAnteriores = [
            'id_historial' => $historial->id,
            'tipo' => TipoImagen::HistorialClinico->value,
        ];
        $imagen->delete();
        $auditoria->registrar('historial.imagen_eliminada', $imagen, $datosAnteriores, request: $request);

        return Redirect::route('admin.pacientes.historial.edit', [$paciente, $historial]);
    }

    public function pdf(Paciente $paciente): HttpResponse
    {
        $consultas = $paciente->historialClinico()
            ->with(['doctor:id,nombre,apellido'])
            ->orderByDesc('fecha_consulta')
            ->get();

        $pdf = Pdf::loadView('pdf.historial', [
            'paciente' => $paciente,
            'consultas' => $consultas,
        ]);

        return $pdf->download("historial-{$paciente->id}.pdf");
    }

    private function verificarPertenece(Paciente $paciente, HistorialClinico $historial): void
    {
        abort_unless($historial->id_paciente === $paciente->id, 404);
    }

    /** @return array<string, int|string|null> */
    private function datosAuditoria(HistorialClinico $historial): array
    {
        return [
            'id_paciente' => $historial->id_paciente,
            'id_cita' => $historial->id_cita,
            'fecha_consulta' => $historial->fecha_consulta?->toDateString(),
            'tipo_consulta' => $historial->tipo_consulta?->value,
        ];
    }

    private function validated(Request $request, Paciente $paciente): array
    {
        return $request->validate([
            'id_cita' => ['nullable', 'exists:citas,id,id_paciente,'.$paciente->id],
            'fecha_consulta' => ['required', 'date'],
            'tipo_consulta' => ['required', 'in:primera_vez,seguimiento,urgencia,control'],
            'motivo_consulta' => ['required', 'string'],
            'diagnostico' => ['nullable', 'string'],
            'tratamiento_realizado' => ['nullable', 'string'],
            'medicamentos_recetados' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'notas_doctor' => ['nullable', 'string'],
        ]);
    }
}

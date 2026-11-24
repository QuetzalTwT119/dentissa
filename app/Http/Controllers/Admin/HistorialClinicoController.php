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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HistorialClinicoController extends Controller
{
    public function index(Request $request, Paciente $paciente): View
    {
        return view('admin.historial.index', [
            'paciente' => $paciente,
            'consultas' => $paciente->historialClinico()
                ->with(['doctor:id,nombre,apellido', 'cita', 'imagenes'])
                ->orderByDesc('fecha_consulta')
                ->paginate($this->paginationPageSize($request)),
            'consentimientos' => $paciente->consentimientos()->with('doctor:id,nombre,apellido')->latest()->get(),
            'planes' => $paciente->planes()->with('pasos')->orderByRaw("estado = 'activo' desc")->latest()->get(),
            // Pagos (24/11/2026): solo para quien tiene el módulo.
            'pagos' => $request->user()->puede('pagos') ? $paciente->pagos()->with('cita.tratamiento:id,nombre')->orderByDesc('fecha')->orderByDesc('id')->limit(10)->get() : null,
            'resumenPagos' => $request->user()->puede('pagos') ? PagoController::resumenPaciente($paciente) : null,
        ]);
    }

    public function create(Request $request, Paciente $paciente): View
    {
        return view('admin.historial.create', [
            'paciente' => $paciente,
            // Desde la Agenda ("Registrar consulta"): solo si la cita es de este paciente.
            'citaElegida' => $request->filled('id_cita') ? $paciente->citas()->find($request->integer('id_cita')) : null,
            'citas' => $paciente->citas()->orderByDesc('fecha_hora')->get(['id', 'fecha_hora']),
        ]);
    }

    public function store(Request $request, Paciente $paciente, AuditoriaService $auditoria): RedirectResponse
    {
        $validated = $this->validated($request, $paciente);

        DB::transaction(function () use ($paciente, $validated, $request, $auditoria) {
            $historial = $paciente->historialClinico()->create([
                ...$validated,
                'id_doctor' => $request->user()->id,
            ]);

            $auditoria->registrar('historial.creado', $historial, datosNuevos: $this->datosAuditoria($historial), request: $request);
        });

        return Redirect::route('admin.pacientes.historial.index', $paciente)->with('success', "Consulta agregada al historial de {$paciente->nombre_completo}.");
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

        $datosActualizados = $this->validated($request, $paciente);

        DB::transaction(function () use ($historial, $datosActualizados, $datosAnteriores, $auditoria, $request) {
            $historial->update($datosActualizados);
            $auditoria->registrar('historial.actualizado', $historial, $datosAnteriores, $this->datosAuditoria($historial), $request);
        });

        return Redirect::route('admin.pacientes.historial.index', $paciente)->with('success', 'Consulta guardada.');
    }

    public function destroy(Request $request, Paciente $paciente, HistorialClinico $historial, AuditoriaService $auditoria): RedirectResponse
    {
        $this->verificarPertenece($paciente, $historial);

        $datosAnteriores = $this->datosAuditoria($historial);

        DB::transaction(function () use ($historial, $datosAnteriores, $auditoria, $request) {
            $historial->delete();
            $auditoria->registrar('historial.eliminado', $historial, $datosAnteriores, request: $request);
        });

        return Redirect::route('admin.pacientes.historial.index', $paciente)->with('success', 'Consulta eliminada del historial.');
    }

    public function storeImagen(Request $request, Paciente $paciente, HistorialClinico $historial, AuditoriaService $auditoria): RedirectResponse
    {
        $this->verificarPertenece($paciente, $historial);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $ruta = $request->file('archivo')->store('historial', 'local');

        try {
            DB::transaction(function () use ($request, $historial, $ruta, $auditoria) {
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
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($ruta);

            throw $exception;
        }

        return Redirect::route('admin.pacientes.historial.edit', [$paciente, $historial])->with('success', 'Archivo adjuntado a la consulta.');
    }

    public function destroyImagen(Request $request, Paciente $paciente, HistorialClinico $historial, Imagen $imagen, AuditoriaService $auditoria): RedirectResponse
    {
        $this->verificarPertenece($paciente, $historial);
        abort_unless($imagen->referencia_id === $historial->id && $imagen->tipo === TipoImagen::HistorialClinico, 404);

        $datosAnteriores = [
            'id_historial' => $historial->id,
            'tipo' => TipoImagen::HistorialClinico->value,
        ];
        DB::transaction(function () use ($imagen, $datosAnteriores, $auditoria, $request) {
            $imagen->delete();
            $auditoria->registrar('historial.imagen_eliminada', $imagen, $datosAnteriores, request: $request);
        });

        return Redirect::route('admin.pacientes.historial.edit', [$paciente, $historial])->with('success', 'Archivo eliminado de la consulta.');
    }

    public function pdf(Paciente $paciente): HttpResponse|RedirectResponse
    {
        $consultas = $paciente->historialClinico()
            ->with(['doctor:id,nombre,apellido'])
            ->orderByDesc('fecha_consulta')
            ->get();

        // Con historia clínica o exploración también hay PDF aunque aún no haya consultas.
        $exploracion = $paciente->exploracionesClinicas()->with('doctor:id,nombre,apellido')->first();
        if ($consultas->isEmpty() && ! $paciente->historiaClinica && ! $exploracion) {
            return Redirect::route('admin.pacientes.historial.index', $paciente)
                ->with('status', 'Aún no hay historia clínica ni consultas para generar el PDF.');
        }

        $pdf = Pdf::loadView('pdf.historial', [
            'paciente' => $paciente,
            'consultas' => $consultas,
            'exploracion' => $exploracion,
        ]);

        return $pdf->download("historial-{$paciente->id}.pdf");
    }

    /**
     * Receta en PDF de una consulta (24/11/2026). Necesita medicamentos y la
     * cédula profesional de quien atendió (Mi perfil). Se abre en el navegador
     * para imprimirla y queda en Control de actividades.
     */
    public function receta(Request $request, Paciente $paciente, HistorialClinico $historial, AuditoriaService $auditoria): HttpResponse|RedirectResponse
    {
        $this->verificarPertenece($paciente, $historial);
        $volver = Redirect::route('admin.pacientes.historial.index', $paciente);

        if (blank($historial->medicamentos_recetados)) {
            return $volver->with('error', 'Esa consulta no tiene medicamentos recetados: escríbelos en «Editar» para imprimir la receta.');
        }

        $doctor = $historial->doctor;
        if (! $doctor || blank($doctor->cedula_profesional)) {
            $quien = $doctor ? "{$doctor->nombre} {$doctor->apellido}" : 'quien atendió';

            return $volver->with('error', "Para imprimir la receta falta la cédula profesional de {$quien}: se agrega en Mi perfil.");
        }

        $historia = $paciente->historiaClinica;
        $alergias = $historia ? \App\Models\HistoriaClinica::nombres($historia->alergias, \App\Models\HistoriaClinica::ALERGIAS) : [];
        if ($historia && filled($historia->alergias_otras)) {
            $alergias[] = trim($historia->alergias_otras);
        }

        DB::transaction(fn () => $auditoria->registrar('receta.generada', $historial, datosNuevos: ['id_paciente' => $paciente->id], request: $request));

        return Pdf::loadView('pdf.receta', [
            'paciente' => $paciente,
            'consulta' => $historial,
            'doctor' => $doctor,
            'edad' => $historia?->edad(),
            'alergias' => implode(', ', $alergias),
        ])->setPaper('letter')->stream("receta-{$paciente->id}-{$historial->id}.pdf");
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
            'dientes' => ['nullable', 'string', 'max:100'],
            'medicamentos_recetados' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'notas_doctor' => ['nullable', 'string'],
        ]);
    }
}

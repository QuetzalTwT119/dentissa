<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\PlanTratamiento;
use App\Models\PlanTratamientoPaso;
use App\Models\Tratamiento;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

/**
 * Plan de tratamiento por paciente (24/11/2026). Se crea con un título y se
 * llena paso por paso (procedimiento, dientes, costo). Marcar un paso como
 * hecho guarda la fecha; el avance sale solo. Módulo "pacientes".
 */
class PlanTratamientoController extends Controller
{
    public function create(Paciente $paciente): View
    {
        return view('admin.planes.create', ['paciente' => $paciente]);
    }

    public function store(Request $request, Paciente $paciente, AuditoriaService $auditoria): RedirectResponse
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ], ['titulo.required' => 'Ponle un nombre al plan (por ejemplo, «Ortodoncia» o «Rehabilitación»).']);

        $plan = DB::transaction(function () use ($validated, $paciente, $request, $auditoria) {
            $plan = PlanTratamiento::create($validated + ['id_paciente' => $paciente->id, 'id_doctor' => $request->user()->id, 'estado' => 'activo']);
            $auditoria->registrar('plan.creado', $plan, datosNuevos: ['id_paciente' => $paciente->id, 'titulo' => $plan->titulo], request: $request);

            return $plan;
        });

        return Redirect::route('admin.pacientes.planes.show', [$paciente, $plan])
            ->with('success', 'Plan creado. Ahora agrega sus pasos.');
    }

    public function show(Paciente $paciente, PlanTratamiento $plan): View
    {
        $this->verificar($paciente, $plan);

        return view('admin.planes.show', [
            'paciente' => $paciente,
            'plan' => $plan->load(['pasos', 'doctor:id,nombre,apellido']),
            'tratamientos' => Tratamiento::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'precio_referencia']),
        ]);
    }

    public function update(Request $request, Paciente $paciente, PlanTratamiento $plan, AuditoriaService $auditoria): RedirectResponse
    {
        $this->verificar($paciente, $plan);
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:120'],
            'estado' => ['required', Rule::in(array_keys(PlanTratamiento::ESTADOS))],
            'notas' => ['nullable', 'string', 'max:2000'],
        ]);

        $auditoria->registrarCambio($request, 'plan.actualizado', $plan, ['titulo', 'estado'], fn () => $plan->update($validated));

        return Redirect::route('admin.pacientes.planes.show', [$paciente, $plan])->with('success', 'Plan guardado.');
    }

    public function agregarPaso(Request $request, Paciente $paciente, PlanTratamiento $plan): RedirectResponse
    {
        $this->verificar($paciente, $plan);
        $validated = $request->validate([
            'descripcion' => ['required', 'string', 'max:150'],
            'dientes' => ['nullable', 'string', 'max:100'],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ], ['descripcion.required' => 'Escribe el procedimiento del paso.']);

        $tratamiento = Tratamiento::where('nombre', $validated['descripcion'])->first();
        $plan->pasos()->create($validated + [
            'id_tratamiento' => $tratamiento?->id,
            'orden' => (int) $plan->pasos()->max('orden') + 1,
        ]);

        return Redirect::to(route('admin.pacientes.planes.show', [$paciente, $plan]).'#pasos')->with('success', 'Paso agregado.');
    }

    /** Marcar o desmarcar como hecho (guarda la fecha de hoy). */
    public function alternarPaso(Request $request, Paciente $paciente, PlanTratamiento $plan, PlanTratamientoPaso $paso, AuditoriaService $auditoria): RedirectResponse
    {
        $this->verificar($paciente, $plan, $paso);
        DB::transaction(function () use ($paso, $plan, $request, $auditoria) {
            $paso->update(['hecho_en' => $paso->hecho() ? null : now()->toDateString()]);
            $auditoria->registrar($paso->hecho() ? 'plan.paso_hecho' : 'plan.paso_pendiente', $plan, datosNuevos: ['paso' => $paso->descripcion], request: $request);
        });

        // Todos hechos: se propone terminar el plan (no se cierra solo).
        $plan->load('pasos');
        $mensaje = $plan->avance() === 100 && $plan->estado === 'activo'
            ? 'Todos los pasos están hechos. Si ya terminó, cambia el estado a «Terminado».'
            : ($paso->hecho() ? 'Paso marcado como hecho.' : 'Paso marcado como pendiente.');

        return Redirect::to(route('admin.pacientes.planes.show', [$paciente, $plan]).'#pasos')->with('success', $mensaje);
    }

    public function quitarPaso(Paciente $paciente, PlanTratamiento $plan, PlanTratamientoPaso $paso): RedirectResponse
    {
        $this->verificar($paciente, $plan, $paso);
        $paso->delete();

        return Redirect::to(route('admin.pacientes.planes.show', [$paciente, $plan]).'#pasos')->with('success', 'Paso quitado.');
    }

    private function verificar(Paciente $paciente, PlanTratamiento $plan, ?PlanTratamientoPaso $paso = null): void
    {
        abort_unless($plan->id_paciente === $paciente->id && (! $paso || $paso->id_plan === $plan->id), 404);
    }
}

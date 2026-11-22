<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $this->filtros($request, [
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $pacientes = Paciente::withCount('historialClinico')
            ->when(filled($validated['buscar'] ?? null), function ($query) use ($validated) {
                $termino = $validated['buscar'];
                $query->where(function ($q) use ($termino) {
                    $q->whereContiene('nombre', $termino)
                        ->orWhereContiene('apellido', $termino)
                        ->orWhereContiene('email', $termino)
                        ->orWhereContiene('telefono', $termino);
                });
            })
            ->orderBy('nombre')
            ->paginate($this->paginationPageSize($request))
            ->withQueryString();

        return view('admin.pacientes.index', [
            'pacientes' => $pacientes,
            'filtros' => $validated,
        ]);
    }

    public function edit(Paciente $paciente): View
    {
        return view('admin.pacientes.edit', [
            'paciente' => $paciente,
        ]);
    }

    public function update(Request $request, Paciente $paciente, AuditoriaService $auditoria): RedirectResponse
    {

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => [
                'required', 'string', 'max:20',
                'regex:/^(?:\\+?52[\\s().-]*)?(?:[0-9][\\s().-]*){10}$/',
            ],
            'email' => [
                'nullable', 'email', 'max:100',
                Rule::unique('pacientes', 'email')->ignore($paciente->id),
            ],
        ]);

        $camposModificados = collect(['nombre', 'apellido', 'telefono', 'email'])
            ->filter(fn (string $campo) => $paciente->getAttribute($campo) !== ($validated[$campo] ?? null))
            ->values()
            ->all();

        DB::transaction(function () use ($paciente, $validated, $camposModificados, $auditoria, $request) {
            $paciente->update([
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'telefono' => $validated['telefono'],
                'email' => $validated['email'] ?? null,
                // CURP, RFC, INE y tutor ya no se editan (22/11/2026): lo guardado se conserva.
            ]);

            $auditoria->registrar('paciente.datos_actualizados', $paciente, datosNuevos: [
                'campos_modificados' => $camposModificados,
            ], request: $request);
        });

        return Redirect::route('admin.pacientes.index')->with('success', "Datos de {$paciente->nombre_completo} guardados.");
    }
}

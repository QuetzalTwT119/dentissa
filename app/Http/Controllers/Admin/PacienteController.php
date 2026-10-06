<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $pacientes = Paciente::withCount('historialClinico')
            ->when($request->filled('buscar'), function ($query) use ($validated) {
                $termino = $validated['buscar'];
                $query->where(function ($q) use ($termino) {
                    $q->where('nombre', 'like', "%{$termino}%")
                        ->orWhere('apellido', 'like', "%{$termino}%")
                        ->orWhere('email', 'like', "%{$termino}%");
                });
            })
            ->orderBy('nombre')
            ->get();

        return view('admin.pacientes.index', [
            'pacientes' => $pacientes,
            'filtros' => $request->only('buscar'),
        ]);
    }

    public function edit(Paciente $paciente): View
    {
        return view('admin.pacientes.edit', [
            'paciente' => $paciente,
            'tutoresDisponibles' => Paciente::where('id', '!=', $paciente->id)
                ->get()
                ->map(fn (Paciente $p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre_completo,
                ])
                ->values(),
        ]);
    }

    public function update(Request $request, Paciente $paciente): RedirectResponse
    {
        $request->merge([
            'curp' => $request->filled('curp') ? strtoupper($request->string('curp')) : null,
            'rfc' => $request->filled('rfc') ? strtoupper($request->string('rfc')) : null,
            'ine_clave' => $request->filled('ine_clave') ? strtoupper($request->string('ine_clave')) : null,
        ]);

        $validated = $request->validate([
            'curp' => [
                'nullable', 'string', 'size:18',
                'regex:/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]{2}$/',
                Rule::unique('pacientes', 'curp')->ignore($paciente->id),
            ],
            'rfc' => [
                'nullable', 'string', 'size:13',
                'regex:/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/',
                Rule::unique('pacientes', 'rfc')->ignore($paciente->id),
            ],
            'ine_clave' => [
                'nullable', 'string', 'size:18',
                Rule::unique('pacientes', 'ine_clave')->ignore($paciente->id),
            ],
            'tutor_id' => [
                'nullable', 'integer',
                Rule::exists('pacientes', 'id'),
                Rule::notIn([$paciente->id]),
            ],
        ]);

        $paciente->update([
            'curp' => $validated['curp'] ?? null,
            'rfc' => $validated['rfc'] ?? null,
            'ine_clave' => $validated['ine_clave'] ?? null,
            'tutor_id' => $validated['tutor_id'] ?? null,
        ]);

        return Redirect::route('admin.pacientes.index');
    }
}

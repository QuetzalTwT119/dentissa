<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoCita;
use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Models\Tratamiento;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class ServicioController extends Controller
{
    public function index(): View
    {
        return view('admin.servicios.index', [
            'servicios' => Servicio::withCount('tratamientos')->orderBy('orden')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.servicios.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('servicios', 'public');
        }

        Servicio::create($data);

        return Redirect::route('admin.servicios.index');
    }

    public function edit(Servicio $servicio): View
    {
        return view('admin.servicios.edit', [
            'servicio' => $servicio,
            'tratamientos' => Tratamiento::orderBy('nombre')->get(['id', 'nombre']),
            'tratamientosAsignados' => $servicio->tratamientos()->pluck('tratamientos.id'),
        ]);
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $data = $this->validated($request, $servicio->id);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('servicios', 'public');
        } else {
            unset($data['imagen']);
        }

        $servicio->update($data);

        return Redirect::route('admin.servicios.index');
    }

    public function syncTratamientos(Request $request, Servicio $servicio): RedirectResponse
    {
        $validated = $request->validate([
            'tratamiento_ids' => ['array'],
            'tratamiento_ids.*' => ['integer', 'exists:tratamientos,id'],
        ]);

        $servicio->tratamientos()->sync($validated['tratamiento_ids'] ?? []);

        return Redirect::route('admin.servicios.edit', $servicio);
    }

    public function destroy(Servicio $servicio): RedirectResponse
    {
        $tieneCitasActivas = $servicio->citas()
            ->whereNotIn('estado', [EstadoCita::Cancelada, EstadoCita::Completada, EstadoCita::NoAsistio])
            ->exists();

        if ($tieneCitasActivas) {
            return Redirect::route('admin.servicios.index')
                ->with('error', 'No se puede eliminar el servicio: tiene citas activas asociadas.');
        }

        $servicio->delete();

        return Redirect::route('admin.servicios.index');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('servicios', 'nombre')->ignore($ignoreId)],
            'descripcion' => ['nullable', 'string'],
            'icono' => ['nullable', 'string', 'max:50'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'activo' => ['boolean'],
            'orden' => ['integer', 'min:0'],
        ]);
    }
}

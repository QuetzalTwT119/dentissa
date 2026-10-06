<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoCita;
use App\Http\Controllers\Controller;
use App\Models\Tratamiento;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class TratamientoController extends Controller
{
    public function index(): View
    {
        return view('admin.tratamientos.index', [
            'tratamientos' => Tratamiento::withCount('servicios')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.tratamientos.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('tratamientos', 'public');
        }

        Tratamiento::create($data);

        return Redirect::route('admin.tratamientos.index');
    }

    public function edit(Tratamiento $tratamiento): View
    {
        return view('admin.tratamientos.edit', [
            'tratamiento' => $tratamiento,
        ]);
    }

    public function update(Request $request, Tratamiento $tratamiento): RedirectResponse
    {
        $data = $this->validated($request, $tratamiento->id);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('tratamientos', 'public');
        } else {
            unset($data['imagen']);
        }

        $tratamiento->update($data);

        return Redirect::route('admin.tratamientos.index');
    }

    public function destroy(Tratamiento $tratamiento): RedirectResponse
    {
        $tieneCitasActivas = $tratamiento->citas()
            ->whereNotIn('estado', [EstadoCita::Cancelada, EstadoCita::Completada, EstadoCita::NoAsistio])
            ->exists();

        if ($tieneCitasActivas) {
            return Redirect::route('admin.tratamientos.index')
                ->with('error', 'No se puede eliminar el tratamiento: tiene citas activas asociadas.');
        }

        $tratamiento->delete();

        return Redirect::route('admin.tratamientos.index');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150', Rule::unique('tratamientos', 'nombre')->ignore($ignoreId)],
            'descripcion' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:50'],
            'duracion_estimada' => ['nullable', 'integer', 'min:0'],
            'precio_referencia' => ['nullable', 'numeric', 'min:0'],
            'sesiones_requeridas' => ['integer', 'min:1'],
            'requiere_autorizacion' => ['boolean'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'activo' => ['boolean'],
        ]);
    }
}

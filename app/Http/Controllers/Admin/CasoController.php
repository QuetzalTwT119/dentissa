<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoImagen;
use App\Http\Controllers\Controller;
use App\Models\Caso;
use App\Models\Imagen;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CasoController extends Controller
{
    public function index(Request $request): View
    {
        // Filtros globales (x-filtro.barra, 22/11/2026). Buscar = texto libre.
        $filtros = $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'in:1,0'],
        ]);

        return view('admin.casos.index', [
            'casos' => Caso::withCount('imagenes')
                ->when($filtros['buscar'] ?? null, fn ($q, $texto) => $q->where('titulo', 'like', '%'.$texto.'%'))
                ->when(isset($filtros['activo']), fn ($q) => $q->where('activo', $filtros['activo'] === '1'))
                ->orderBy('orden')
                ->paginate($this->paginationPageSize($request))
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.casos.create');
    }

    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $caso = $auditoria->registrarCambio(
            $request,
            'caso.creado',
            new Caso,
            ['titulo', 'activo', 'orden'],
            fn () => Caso::create($this->validated($request)),
        );

        return Redirect::route('admin.casos.edit', $caso)->with('success', "Caso «{$caso->titulo}» creado. Ahora agrega sus fotos de antes y después.");
    }

    public function edit(Caso $caso): View
    {
        return view('admin.casos.edit', [
            'caso' => $caso->load('imagenes'),
        ]);
    }

    public function update(Request $request, Caso $caso, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'caso.actualizado',
            $caso,
            ['titulo', 'activo', 'orden'],
            fn () => $caso->update($this->validated($request)),
        );

        return Redirect::route('admin.casos.index')->with('success', "Caso «{$caso->titulo}» guardado.");
    }

    public function destroy(Request $request, Caso $caso, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'caso.eliminado',
            $caso,
            ['titulo'],
            fn () => $caso->delete(),
            eliminado: true,
        );

        return Redirect::route('admin.casos.index')->with('success', "Caso «{$caso->titulo}» eliminado.");
    }

    public function storeImagen(Request $request, Caso $caso, AuditoriaService $auditoria): RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'image', 'max:2048'],
        ]);

        $auditoria->registrarCambio(
            $request,
            'caso.imagen_agregada',
            new Imagen,
            ['titulo', 'tipo', 'referencia_id'],
            fn () => Imagen::create([
                'titulo' => $request->file('archivo')->getClientOriginalName(),
                'url' => $request->file('archivo')->store('casos', 'public'),
                'tipo' => TipoImagen::Caso,
                'referencia_id' => $caso->id,
            ]),
        );

        return Redirect::route('admin.casos.edit', $caso)->with('success', 'Foto agregada al caso.');
    }

    public function destroyImagen(Request $request, Caso $caso, Imagen $imagen, AuditoriaService $auditoria): RedirectResponse
    {
        abort_unless($imagen->referencia_id === $caso->id && $imagen->tipo === TipoImagen::Caso, 404);

        $auditoria->registrarCambio(
            $request,
            'caso.imagen_eliminada',
            $imagen,
            ['titulo', 'tipo', 'referencia_id'],
            fn () => $imagen->delete(),
            eliminado: true,
        );

        return Redirect::route('admin.casos.edit', $caso)->with('success', 'Foto eliminada del caso.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['boolean'],
            'orden' => ['integer', 'min:0'],
        ]);
    }
}

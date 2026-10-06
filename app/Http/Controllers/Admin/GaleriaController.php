<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoImagen;
use App\Http\Controllers\Controller;
use App\Models\Imagen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class GaleriaController extends Controller
{
    public function index(): View
    {
        return view('admin.galeria.index', [
            'imagenes' => Imagen::where('tipo', TipoImagen::Galeria)
                ->orderBy('orden')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.galeria.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titulo' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['required', 'image', 'max:2048'],
            'orden' => ['integer', 'min:0'],
            'activa' => ['boolean'],
        ]);

        Imagen::create([
            'titulo' => $validated['titulo'] ?? null,
            'descripcion' => $validated['descripcion'] ?? null,
            'url' => $request->file('imagen')->store('galeria', 'public'),
            'tipo' => TipoImagen::Galeria,
            'orden' => $validated['orden'] ?? 0,
            'activa' => $validated['activa'] ?? true,
        ]);

        return Redirect::route('admin.galeria.index');
    }

    public function edit(Imagen $imagen): View
    {
        return view('admin.galeria.edit', [
            'imagen' => $imagen,
        ]);
    }

    public function update(Request $request, Imagen $imagen): RedirectResponse
    {
        $validated = $request->validate([
            'titulo' => ['nullable', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'orden' => ['integer', 'min:0'],
            'activa' => ['boolean'],
        ]);

        $data = [
            'titulo' => $validated['titulo'] ?? null,
            'descripcion' => $validated['descripcion'] ?? null,
            'orden' => $validated['orden'] ?? 0,
            'activa' => $validated['activa'] ?? true,
        ];

        if ($request->hasFile('imagen')) {
            $data['url'] = $request->file('imagen')->store('galeria', 'public');
        }

        $imagen->update($data);

        return Redirect::route('admin.galeria.index');
    }

    public function destroy(Imagen $imagen): RedirectResponse
    {
        $imagen->delete();

        return Redirect::route('admin.galeria.index');
    }
}

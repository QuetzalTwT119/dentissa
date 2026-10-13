<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreguntaFrecuente;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PreguntaFrecuenteController extends Controller
{
    public function index(): View
    {
        return view('admin.preguntas-frecuentes.index', [
            'preguntas' => PreguntaFrecuente::orderBy('orden')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.preguntas-frecuentes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        PreguntaFrecuente::create($this->validated($request));

        return Redirect::route('admin.preguntas-frecuentes.index');
    }

    // El parámetro de ruta se llama "preguntas_frecuente" (Route::resource
    // singulariza solo la última palabra de "preguntas-frecuentes"). Debe
    // coincidir tal cual para que el binding implícito de modelo funcione.
    public function edit(PreguntaFrecuente $preguntas_frecuente): View
    {
        return view('admin.preguntas-frecuentes.edit', [
            'pregunta' => $preguntas_frecuente,
        ]);
    }

    public function update(Request $request, PreguntaFrecuente $preguntas_frecuente): RedirectResponse
    {
        $preguntas_frecuente->update($this->validated($request));

        return Redirect::route('admin.preguntas-frecuentes.index');
    }

    public function destroy(PreguntaFrecuente $preguntas_frecuente): RedirectResponse
    {
        $preguntas_frecuente->delete();

        return Redirect::route('admin.preguntas-frecuentes.index');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'pregunta' => ['required', 'string', 'max:200'],
            'respuesta' => ['required', 'string'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'activa' => ['boolean'],
        ]);

        // Un checkbox desmarcado no se envía en el formulario; normalizarlo
        // aquí evita conservar/publicar por accidente una pregunta inactiva.
        $validated['activa'] = $request->boolean('activa');

        return $validated;
    }
}

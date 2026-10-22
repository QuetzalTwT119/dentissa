<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreguntaFrecuente;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PreguntaFrecuenteController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.preguntas-frecuentes.index', [
            'preguntas' => PreguntaFrecuente::orderBy('orden')->orderBy('id')->paginate($this->paginationPageSize($request)),
        ]);
    }

    public function create(): View
    {
        return view('admin.preguntas-frecuentes.create');
    }

    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'faq.creada',
            new PreguntaFrecuente,
            ['pregunta', 'respuesta', 'variantes', 'orden', 'activa'],
            fn () => PreguntaFrecuente::create($this->validated($request)),
        );

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

    public function update(Request $request, PreguntaFrecuente $preguntas_frecuente, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'faq.actualizada',
            $preguntas_frecuente,
            ['pregunta', 'respuesta', 'variantes', 'orden', 'activa'],
            fn () => $preguntas_frecuente->update($this->validated($request)),
        );

        return Redirect::route('admin.preguntas-frecuentes.index');
    }

    public function destroy(Request $request, PreguntaFrecuente $preguntas_frecuente, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'faq.eliminada',
            $preguntas_frecuente,
            ['pregunta'],
            fn () => $preguntas_frecuente->delete(),
            eliminado: true,
        );

        return Redirect::route('admin.preguntas-frecuentes.index');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'pregunta' => ['required', 'string', 'max:200'],
            'respuesta' => ['required', 'string'],
            'variantes' => ['nullable', 'string', 'max:4000'],
            'orden' => ['nullable', 'integer', 'min:0'],
            'activa' => ['boolean'],
        ]);

        // Un checkbox desmarcado no se envía en el formulario; normalizarlo
        // aquí evita conservar/publicar por accidente una pregunta inactiva.
        $validated['activa'] = $request->boolean('activa');

        return $validated;
    }
}

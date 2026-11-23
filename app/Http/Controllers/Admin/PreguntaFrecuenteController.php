<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AlternaEstado;
use App\Http\Controllers\Controller;
use App\Models\PreguntaFrecuente;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PreguntaFrecuenteController extends Controller
{
    use AlternaEstado;

    public function index(Request $request): View
    {
        // Filtros globales (x-filtro.barra, 22/11/2026). Buscar = texto libre.
        $filtros = $this->filtros($request, [
            'buscar' => ['nullable', 'string', 'max:100'],
            'activa' => ['nullable', 'in:1,0'],
        ]);

        return view('admin.preguntas-frecuentes.index', [
            'preguntas' => PreguntaFrecuente::query()
                ->when($filtros['buscar'] ?? null, fn ($q, $texto) => $q->where(fn ($w) => $w->whereContiene('pregunta', $texto)->orWhereContiene('respuesta', $texto)))
                ->when(isset($filtros['activa']), fn ($q) => $q->where('activa', $filtros['activa'] === '1'))
                ->orderBy('orden')->orderBy('id')
                ->paginate($this->paginationPageSize($request))
                ->withQueryString(),
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

        return Redirect::route('admin.preguntas-frecuentes.index')->with('success', 'Pregunta frecuente creada.');
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

        return Redirect::route('admin.preguntas-frecuentes.index')->with('success', 'Pregunta frecuente guardada.');
    }

    /** Interruptor de la lista (x-admin.interruptor-estado). */
    public function estado(Request $request, PreguntaFrecuente $preguntas_frecuente): RedirectResponse
    {
        return $this->alternarCampo($request, $preguntas_frecuente, 'activa', 'faq.estado_cambiado', 'Pregunta visible: se muestra en la portada y el asistente la usa.', 'Pregunta oculta: ya no se muestra en la portada ni la usa el asistente.');
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

        return Redirect::route('admin.preguntas-frecuentes.index')->with('success', 'Pregunta frecuente eliminada.');
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

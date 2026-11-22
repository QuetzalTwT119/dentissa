<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoCita;
use App\Http\Controllers\Controller;
use App\Models\Tratamiento;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class TratamientoController extends Controller
{
    public function index(Request $request): View
    {
        // Filtros globales (x-filtro.barra, 22/11/2026). Buscar = texto libre.
        $filtros = $this->filtros($request, [
            'buscar' => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'in:1,0'],
            'servicio' => ['nullable', 'integer'],
        ]);

        return view('admin.tratamientos.index', [
            // citas_activas: con alguna, no se puede eliminar (22/11/2026, la lista lo dice).
            'tratamientos' => Tratamiento::query()
                ->withCount(['citas as citas_activas' => fn ($c) => $c->whereNotIn('estado', [\App\Enums\EstadoCita::Cancelada, \App\Enums\EstadoCita::Completada, \App\Enums\EstadoCita::NoAsistio])])
                ->with(['servicios' => fn ($q) => $q->select('servicios.id', 'servicios.nombre', 'servicios.activo')
                    ->withCount(['tratamientos as tratamientos_activos_count' => fn ($t) => $t->where('tratamientos.activo', true)])])
                ->when($filtros['buscar'] ?? null, fn ($q, $texto) => $q->whereContiene('nombre', $texto))
                ->when(isset($filtros['activo']), fn ($q) => $q->where('activo', $filtros['activo'] === '1'))
                ->when($filtros['servicio'] ?? null, fn ($q, $id) => $q->whereHas('servicios', fn ($s) => $s->whereKey($id)))
                ->orderBy('nombre')
                ->paginate($this->paginationPageSize($request))
                ->withQueryString(),
            'serviciosFiltro' => \App\Models\Servicio::orderBy('orden')->orderBy('id')->pluck('nombre', 'id')->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.tratamientos.create');
    }

    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        // Con precio nace publicado; sin precio se guarda inactivo hasta que lo tenga.
        $data = $this->validated($request);
        $data['activo'] = ($data['precio_referencia'] ?? 0) > 0;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('tratamientos', 'public');
        }

        $auditoria->registrarCambio(
            $request,
            'tratamiento.creado',
            new Tratamiento,
            ['nombre', 'duracion_estimada', 'precio_referencia', 'activo'],
            fn () => Tratamiento::create($data),
        );

        return Redirect::route('admin.tratamientos.index')->with('success', $data['activo']
            ? "Tratamiento «{$request->input('nombre')}» creado."
            : "Tratamiento «{$request->input('nombre')}» creado sin precio: no se publicará hasta que le pongas precio.");
    }

    public function edit(Tratamiento $tratamiento): View
    {
        return view('admin.tratamientos.edit', [
            'tratamiento' => $tratamiento,
        ]);
    }

    public function update(Request $request, Tratamiento $tratamiento, AuditoriaService $auditoria): RedirectResponse
    {
        $data = $this->validated($request, $tratamiento->id);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('tratamientos', 'public');
        } else {
            unset($data['imagen']);
        }

        $auditoria->registrarCambio(
            $request,
            'tratamiento.actualizado',
            $tratamiento,
            ['nombre', 'duracion_estimada', 'precio_referencia', 'activo'],
            // Si a uno publicado le quitan el precio, deja de publicarse (activo ⇒ tiene precio).
            fn () => $tratamiento->update($data + (($data['precio_referencia'] ?? 0) > 0 ? [] : ['activo' => false])),
        );

        $sinPrecio = ! ($tratamiento->precio_referencia > 0);

        return Redirect::route('admin.tratamientos.index')->with('success', $sinPrecio
            ? "Tratamiento «{$tratamiento->nombre}» guardado sin precio: no se publica hasta que le pongas precio."
            : "Tratamiento «{$tratamiento->nombre}» guardado.");
    }

    /**
     * Activa o desactiva desde la lista, sin abrir el formulario. Un
     * tratamiento inactivo deja de ofrecerse al agendar (en línea y en el panel).
     */
    public function alternarEstado(Request $request, Tratamiento $tratamiento, AuditoriaService $auditoria): RedirectResponse
    {
        // Sin precio no se publica: no se puede activar hasta capturarlo.
        if (! $tratamiento->activo && ! ($tratamiento->precio_referencia > 0)) {
            return Redirect::back()->with('error', "Agrega el precio de «{$tratamiento->nombre}» antes de activarlo.");
        }

        $auditoria->registrarCambio(
            $request,
            'tratamiento.estado_cambiado',
            $tratamiento,
            ['activo'],
            fn () => $tratamiento->update(['activo' => ! $tratamiento->activo]),
        );

        return Redirect::back()->with('success', $tratamiento->activo
            ? "«{$tratamiento->nombre}» está activo: ya se puede agendar."
            : "«{$tratamiento->nombre}» está inactivo: ya no se ofrece al agendar.");
    }

    public function destroy(Request $request, Tratamiento $tratamiento, AuditoriaService $auditoria): RedirectResponse
    {
        $tieneCitasActivas = $tratamiento->citas()
            ->whereNotIn('estado', [EstadoCita::Cancelada, EstadoCita::Completada, EstadoCita::NoAsistio])
            ->exists();

        if ($tieneCitasActivas) {
            return Redirect::route('admin.tratamientos.index')
                ->with('error', 'No se puede eliminar el tratamiento: tiene citas activas asociadas.');
        }

        $auditoria->registrarCambio(
            $request,
            'tratamiento.eliminado',
            $tratamiento,
            ['nombre', 'activo'],
            fn () => $tratamiento->delete(),
            eliminado: true,
        );

        return Redirect::route('admin.tratamientos.index')->with('success', "Tratamiento «{$tratamiento->nombre}» eliminado.");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150', Rule::unique('tratamientos', 'nombre')->ignore($ignoreId)],
            'descripcion' => ['nullable', 'string'],
            'duracion_estimada' => ['nullable', 'integer', 'min:0'],
            // Se muestra al paciente. Se puede guardar sin precio, pero no publicar.
            'precio_referencia' => ['nullable', 'numeric', 'min:1', 'max:999999'],
            'sesiones_requeridas' => ['integer', 'min:1'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }
}

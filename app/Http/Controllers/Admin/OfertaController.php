<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Oferta;
use App\Models\Tratamiento;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class OfertaController extends Controller
{
    public function index(): View
    {
        return view('admin.ofertas.index', [
            'ofertas' => Oferta::with('tratamiento:id,nombre')->orderByDesc('fecha_inicio')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.ofertas.create', [
            'tratamientos' => Tratamiento::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('ofertas', 'public');
        }

        Oferta::create($data);

        return Redirect::route('admin.ofertas.index');
    }

    public function edit(Oferta $oferta): View
    {
        return view('admin.ofertas.edit', [
            'oferta' => $oferta,
            'tratamientos' => Tratamiento::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, Oferta $oferta): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('ofertas', 'public');
        } else {
            unset($data['imagen']);
        }

        $oferta->update($data);

        return Redirect::route('admin.ofertas.index');
    }

    public function destroy(Oferta $oferta): RedirectResponse
    {
        $oferta->delete();

        return Redirect::route('admin.ofertas.index');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'id_tratamiento' => ['nullable', 'exists:tratamientos,id'],
            'tipo' => ['required', 'in:descuento,paquete,bonificacion'],
            'valor_descuento' => ['nullable', 'numeric', 'min:0'],
            'tipo_valor' => ['required', 'in:porcentaje,monto_fijo'],
            'precio_especial' => ['nullable', 'numeric', 'min:0'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'cupo_total' => ['nullable', 'integer', 'min:0'],
            'imagen' => ['nullable', 'image', 'max:2048'],
            'activa' => ['boolean'],
        ]);
    }
}

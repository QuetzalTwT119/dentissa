<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HorarioAtencion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class HorarioController extends Controller
{
    private const ORDEN_SEMANA = [1, 2, 3, 4, 5, 6, 0];

    public function edit(): View
    {
        $horarios = HorarioAtencion::all()->keyBy('dia_semana');

        return view('admin.horarios.edit', [
            'horarios' => collect(self::ORDEN_SEMANA)
                ->map(fn (int $dia) => $horarios->get($dia))
                ->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dias' => ['required', 'array', 'size:7'],
            'dias.*.dia_semana' => ['required', 'integer', 'between:0,6'],
            'dias.*.abierto' => ['boolean'],
            'dias.*.hora_apertura' => ['required', 'integer', 'between:0,23'],
            'dias.*.hora_cierre' => ['required', 'integer', 'between:0,23'],
            'dias.*.capacidad_simultanea' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($validated['dias'] as $dia) {
            HorarioAtencion::where('dia_semana', $dia['dia_semana'])->update([
                'abierto' => $dia['abierto'] ?? false,
                'hora_apertura' => $dia['hora_apertura'],
                'hora_cierre' => $dia['hora_cierre'],
                'capacidad_simultanea' => $dia['capacidad_simultanea'],
            ]);
        }

        return Redirect::route('admin.horarios.edit');
    }
}

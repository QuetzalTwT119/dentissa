<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoCita;
use App\Http\Controllers\Controller;
use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;

/**
 * Días u horas bloqueados (24/11/2026). Vive en Configuración. No se puede
 * bloquear un tiempo que ya tiene citas activas: primero se reagendan o se
 * cancelan (así nadie se queda con una cita en un día cerrado sin saberlo).
 */
class BloqueoAgendaController extends Controller
{
    /** Un rango más largo casi siempre es un error al escribir la fecha. */
    private const DIAS_MAXIMOS = 60;

    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $todoElDia = $request->boolean('todo_el_dia');

        $validated = $request->validate([
            'fecha_inicio' => ['required', 'date', 'after_or_equal:today'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'hora_inicio' => [$todoElDia ? 'nullable' : 'required', 'date_format:H:i'],
            'hora_fin' => [$todoElDia ? 'nullable' : 'required', 'date_format:H:i', ...($todoElDia ? [] : ['after:hora_inicio'])],
            'motivo' => ['required', 'string', 'max:120'],
        ], [
            'fecha_inicio.after_or_equal' => 'No se puede bloquear una fecha pasada.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
            'hora_inicio.required' => 'Indica desde qué hora, o marca «Todo el día».',
            'hora_fin.required' => 'Indica hasta qué hora, o marca «Todo el día».',
            'hora_fin.after' => 'La hora final debe ser después de la inicial.',
            'motivo.required' => 'Escribe el motivo (por ejemplo, «Vacaciones»).',
        ]);

        $inicio = Carbon::parse($validated['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($validated['fecha_fin'] ?? $validated['fecha_inicio'])->startOfDay();

        if (! $todoElDia && ! $inicio->isSameDay($fin)) {
            throw ValidationException::withMessages(['fecha_fin' => 'Para bloquear unas horas, elige un solo día. Para varios días marca «Todo el día».']);
        }
        if ($inicio->diffInDays($fin) >= self::DIAS_MAXIMOS) {
            throw ValidationException::withMessages(['fecha_fin' => 'El bloqueo puede durar hasta '.self::DIAS_MAXIMOS.' días.']);
        }

        $datos = [
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $fin->toDateString(),
            'hora_inicio' => $todoElDia ? null : $validated['hora_inicio'],
            'hora_fin' => $todoElDia ? null : $validated['hora_fin'],
            'motivo' => trim($validated['motivo']),
        ];

        $this->revisarCitasActivas($datos);

        $bloqueo = BloqueoAgenda::create($datos + ['creado_por' => $request->user()->id]);
        $auditoria->registrar('bloqueo.creado', $bloqueo, datosNuevos: $datos, request: $request);

        return Redirect::to(route('admin.configuracion').'#bloqueos')
            ->with('success', 'Bloqueo guardado: '.$bloqueo->descripcion().'. Esos horarios ya no se ofrecen.');
    }

    public function destroy(Request $request, BloqueoAgenda $bloqueo, AuditoriaService $auditoria): RedirectResponse
    {
        $descripcion = $bloqueo->descripcion();
        $auditoria->registrar('bloqueo.eliminado', $bloqueo, datosAnteriores: $bloqueo->only(['fecha_inicio', 'fecha_fin', 'hora_inicio', 'hora_fin', 'motivo']), request: $request);
        $bloqueo->delete();

        return Redirect::to(route('admin.configuracion').'#bloqueos')
            ->with('success', "Se quitó el bloqueo ({$descripcion}). Esos horarios vuelven a ofrecerse.");
    }

    /** Citas agendadas, confirmadas o en curso que caen dentro del bloqueo. */
    private function revisarCitasActivas(array $datos): void
    {
        $citas = Cita::query()
            ->whereIn('estado', [EstadoCita::Agendada, EstadoCita::Confirmada, EstadoCita::EnCurso])
            ->whereDate('fecha_hora', '>=', $datos['fecha_inicio'])
            ->whereDate('fecha_hora', '<=', $datos['fecha_fin'])
            ->orderBy('fecha_hora')
            ->get(['id', 'fecha_hora', 'duracion_estimada']);

        if ($datos['hora_inicio'] !== null) {
            $tramos = collect([[
                Carbon::parse($datos['fecha_inicio'].' '.$datos['hora_inicio']),
                Carbon::parse($datos['fecha_inicio'].' '.$datos['hora_fin']),
            ]]);
            $citas = $citas->filter(fn (Cita $cita) => BloqueoAgenda::chocaCon(
                $tramos,
                $cita->fecha_hora,
                $cita->fecha_hora->copy()->addMinutes((int) $cita->duracion_estimada),
            ));
        }

        if ($citas->isEmpty()) {
            return;
        }

        $ejemplos = $citas->take(3)->map(fn (Cita $cita) => $cita->fecha_hora->copy()->locale('es')->translatedFormat('j M, H:i'))->implode(', ');
        $total = $citas->count();

        throw ValidationException::withMessages([
            'fecha_inicio' => ($total === 1 ? 'Hay 1 cita activa' : "Hay {$total} citas activas")
                ." en ese tiempo ({$ejemplos}".($total > 3 ? '…' : '').'). Reagéndalas o cancélalas en la Agenda antes de bloquear.',
        ]);
    }
}

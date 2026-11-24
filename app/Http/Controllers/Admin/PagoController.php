<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoCita;
use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Pago;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

/**
 * Pagos e ingresos (24/11/2026). Registrar cuánto, cómo y cuándo pagó un
 * paciente (desde su expediente o desde una cita completada) y ver los
 * ingresos por periodo y forma de pago. Módulo "pagos" de config/permisos.php.
 */
class PagoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $this->filtros($request, [
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'metodo' => ['nullable', Rule::in(array_keys(Pago::METODOS))],
        ]);
        $desde = Carbon::parse($filtros['desde'] ?? now()->startOfMonth())->toDateString();
        $hasta = Carbon::parse($filtros['hasta'] ?? now())->toDateString();
        if ($hasta < $desde) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $consulta = Pago::query()
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->when($filtros['metodo'] ?? null, fn ($q, $metodo) => $q->where('metodo', $metodo));

        $porMetodo = (clone $consulta)->selectRaw('metodo, SUM(monto) as total, COUNT(*) as pagos')->groupBy('metodo')->get()->keyBy('metodo');

        return view('admin.pagos.index', [
            'pagos' => (clone $consulta)->with(['paciente:id,nombre,apellido', 'registradoPor:id,nombre,apellido'])
                ->orderByDesc('fecha')->orderByDesc('id')
                ->paginate($this->paginationPageSize($request))->withQueryString(),
            'total' => (float) $porMetodo->sum('total'),
            'cantidad' => (int) $porMetodo->sum('pagos'),
            'porMetodo' => $porMetodo,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    public function create(Request $request): View
    {
        $paciente = Paciente::findOrFail($request->integer('paciente'));
        $citas = $paciente->citas()
            ->with(['tratamiento:id,nombre,precio_referencia', 'oferta'])
            ->whereIn('estado', [EstadoCita::Completada, EstadoCita::EnCurso, EstadoCita::Confirmada, EstadoCita::Agendada])
            ->orderByDesc('fecha_hora')
            ->limit(20)
            ->get();
        $citaElegida = $request->filled('cita') ? $citas->firstWhere('id', $request->integer('cita')) : null;

        return view('admin.pagos.create', [
            'paciente' => $paciente,
            'citas' => $citas,
            'citaElegida' => $citaElegida,
        ]);
    }

    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $validated = $request->validate([
            'id_paciente' => ['required', 'integer', 'exists:pacientes,id'],
            'id_cita' => ['nullable', 'integer', Rule::exists('citas', 'id')->where('id_paciente', $request->integer('id_paciente'))],
            'monto' => ['required', 'numeric', 'min:1', 'max:999999'],
            'metodo' => ['required', Rule::in(array_keys(Pago::METODOS))],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'concepto' => ['nullable', 'string', 'max:150'],
        ], [
            'monto.min' => 'El monto debe ser mayor a cero.',
            'fecha.before_or_equal' => 'La fecha del pago no puede ser futura.',
            'id_cita.exists' => 'Esa cita no es de este paciente.',
        ]);

        $pago = DB::transaction(function () use ($validated, $request, $auditoria) {
            $pago = Pago::create($validated + ['registrado_por' => $request->user()->id]);
            $auditoria->registrar('pago.registrado', $pago, datosNuevos: [
                'id_paciente' => $pago->id_paciente, 'id_cita' => $pago->id_cita, 'monto' => $pago->monto, 'metodo' => $pago->metodo, 'fecha' => $pago->fecha->toDateString(),
            ], request: $request);

            return $pago;
        });

        return Redirect::route('admin.pacientes.historial.index', $pago->id_paciente)
            ->with('success', 'Pago de '.Pago::dinero($pago->monto).' registrado.');
    }

    public function destroy(Request $request, Pago $pago, AuditoriaService $auditoria): RedirectResponse
    {
        $paciente = $pago->id_paciente;
        DB::transaction(function () use ($pago, $request, $auditoria) {
            $auditoria->registrar('pago.eliminado', $pago, datosAnteriores: [
                'id_paciente' => $pago->id_paciente, 'id_cita' => $pago->id_cita, 'monto' => $pago->monto, 'metodo' => $pago->metodo, 'fecha' => $pago->fecha->toDateString(),
            ], request: $request);
            $pago->delete();
        });

        return Redirect::to(url()->previous() ?: route('admin.pacientes.historial.index', $paciente))
            ->with('success', 'Pago eliminado.');
    }

    /** Lo que se cobró y lo que falta de las citas atendidas, para el expediente. */
    public static function resumenPaciente(Paciente $paciente): array
    {
        $pagado = (float) $paciente->pagos()->sum('monto');
        $estimado = $paciente->citas()
            ->with(['tratamiento:id,precio_referencia', 'oferta'])
            ->where('estado', EstadoCita::Completada)
            ->get()
            ->sum(fn (Cita $cita) => (float) $cita->precioEstimado());

        return [
            'pagado' => $pagado,
            'estimado' => $estimado,
            'porCobrar' => max(0, $estimado - $pagado),
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExploracionClinica;
use App\Models\Paciente;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

/**
 * Exploración clínica odontológica (23/11/2026): examen de la boca, ATM, encías,
 * higiene (O'Leary), problemas por diente, radiografías, odontograma y
 * diagnósticos. Módulo "pacientes" (la asistente no la ve). Control de
 * actividades guarda solo qué secciones cambiaron, nunca su contenido.
 */
class ExploracionClinicaController extends Controller
{
    public function create(Paciente $paciente): View
    {
        // Parte de la última: el odontograma casi no cambia entre visitas.
        $anterior = $paciente->exploracionesClinicas()->latest('fecha')->latest('id')->first();

        return view('admin.exploracion-clinica.form', [
            'paciente' => $paciente,
            'exploracion' => new ExploracionClinica([
                'fecha' => now(),
                'odontograma' => $anterior?->odontograma ?? [],
            ]),
            'basadaEn' => $anterior,
        ]);
    }

    public function store(Request $request, Paciente $paciente, AuditoriaService $auditoria): RedirectResponse
    {
        $exploracion = new ExploracionClinica(['id_paciente' => $paciente->id]);
        $this->guardar($request, $exploracion, $auditoria, 'exploracion_clinica.creada');

        return Redirect::route('admin.pacientes.historial.index', $paciente)
            ->with('success', 'Exploración clínica guardada.');
    }

    public function edit(Paciente $paciente, ExploracionClinica $exploracion): View
    {
        abort_unless($exploracion->id_paciente === $paciente->id, 404);

        return view('admin.exploracion-clinica.form', [
            'paciente' => $paciente,
            'exploracion' => $exploracion,
            'basadaEn' => null,
        ]);
    }

    public function update(Request $request, Paciente $paciente, ExploracionClinica $exploracion, AuditoriaService $auditoria): RedirectResponse
    {
        abort_unless($exploracion->id_paciente === $paciente->id, 404);
        $this->guardar($request, $exploracion, $auditoria, 'exploracion_clinica.actualizada');

        return Redirect::route('admin.pacientes.historial.index', $paciente)
            ->with('success', 'Exploración clínica actualizada.');
    }

    /** Ordena las claves de un arreglo (y sus hijos) para compararlo sin importar el orden. */
    private static function normalizar(mixed $valor): mixed
    {
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }
        if (! is_array($valor)) {
            return $valor;
        }
        $valor = array_map(fn ($v) => self::normalizar($v), $valor);
        if (! array_is_list($valor)) {
            ksort($valor);
        }

        return $valor;
    }

    private function guardar(Request $request, ExploracionClinica $exploracion, AuditoriaService $auditoria, string $accion): void
    {
        $dientes = ExploracionClinica::dientesValidos();

        $datos = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'estomatologico' => ['nullable', 'array'],
            'estomatologico.*.normal' => ['nullable', 'boolean'],
            'estomatologico.*.hallazgo' => ['nullable', 'string', 'max:300'],
            'atm' => ['nullable', 'string', 'max:1000'],
            'periodontal' => ['nullable', 'array'],
            'periodontal.*' => ['nullable', 'string', 'max:100'],
            'higiene_oral' => ['nullable', Rule::in(array_keys(ExploracionClinica::HIGIENE))],
            'oleary_dientes' => ['nullable', 'integer', 'min:1', 'max:52'],
            'oleary_superficies_placa' => ['nullable', 'integer', 'min:0', 'max:208', 'lte:'.((int) $request->input('oleary_dientes', 0) * 4)],
            'pulpares' => ['nullable', 'array', 'max:32'],
            'pulpares.*.diente' => ['nullable', 'integer', Rule::in($dientes)],
            'pulpares.*.signos' => ['nullable', 'string', 'max:300'],
            'pulpares.*.diagnostico' => ['nullable', 'string', 'max:300'],
            'radiografica' => ['nullable', 'array', 'max:32'],
            'radiografica.*.diente' => ['nullable', 'integer', Rule::in($dientes)],
            'radiografica.*.hallazgo' => ['nullable', 'string', 'max:300'],
            'otros_hallazgos' => ['nullable', 'string', 'max:2000'],
            'odontograma' => ['nullable', 'array'],
            'odontograma.*' => ['nullable', Rule::in(array_keys(ExploracionClinica::ESTADOS_DIENTE))],
            'diagnosticos' => ['nullable', 'string', 'max:2000'],
        ], [
            'fecha.before_or_equal' => 'La fecha de la exploración no puede ser futura.',
            'oleary_superficies_placa.lte' => 'Las superficies con placa no pueden ser más que dientes × 4.',
            'pulpares.*.diente.in' => 'Ese número de diente no existe.',
            'radiografica.*.diente.in' => 'Ese número de diente no existe.',
        ]);

        // Solo lo que trae algo: así no se guardan renglones vacíos.
        $estructuras = collect($datos['estomatologico'] ?? [])
            ->only(array_keys(ExploracionClinica::ESTRUCTURAS))
            ->map(fn ($e) => ['normal' => (bool) ($e['normal'] ?? false), 'hallazgo' => trim((string) ($e['hallazgo'] ?? ''))])
            ->filter(fn ($e) => $e['normal'] || $e['hallazgo'] !== '')
            ->all();
        $periodontal = collect($datos['periodontal'] ?? [])
            ->only(array_keys(ExploracionClinica::PERIODONTAL))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->all();
        $renglones = fn (array $filas, array $campos) => collect($filas)
            ->map(fn ($f) => collect($campos)->mapWithKeys(fn ($c) => [$c => $c === 'diente' ? ($f[$c] ?? null) : trim((string) ($f[$c] ?? ''))])->all())
            ->filter(fn ($f) => collect($f)->filter(fn ($v) => filled($v))->isNotEmpty())
            ->values()->all();
        // El odontograma solo con dientes válidos y un estado.
        $odontograma = collect($datos['odontograma'] ?? [])
            ->filter(fn ($estado, $diente) => filled($estado) && in_array((int) $diente, $dientes, true))
            ->mapWithKeys(fn ($estado, $diente) => [(string) $diente => $estado])
            ->sortKeys()->all();

        $exploracion->fill([
            'id_doctor' => $request->user()->id,
            'fecha' => $datos['fecha'],
            'estomatologico' => $estructuras,
            'atm' => $datos['atm'] ?? null,
            'periodontal' => $periodontal,
            'higiene_oral' => $datos['higiene_oral'] ?? null,
            'oleary_dientes' => $datos['oleary_dientes'] ?? null,
            'oleary_superficies_placa' => $datos['oleary_superficies_placa'] ?? null,
            'pulpares' => $renglones($datos['pulpares'] ?? [], ['diente', 'signos', 'diagnostico']),
            'radiografica' => $renglones($datos['radiografica'] ?? [], ['diente', 'hallazgo']),
            'otros_hallazgos' => $datos['otros_hallazgos'] ?? null,
            'odontograma' => $odontograma,
            'diagnosticos' => $datos['diagnosticos'] ?? null,
        ]);
        // MySQL reordena las claves de los JSON al guardarlos: Eloquent vería "cambiado" un
        // examen idéntico. Se compara sin importar el orden (23/11/2026, visto en test-mysql).
        $cambiados = collect(array_keys($exploracion->getDirty()))
            ->reject(fn ($campo) => in_array($campo, ['id_doctor', 'id_paciente'], true))
            ->reject(fn ($campo) => $exploracion->exists
                && self::normalizar($exploracion->getOriginal($campo)) == self::normalizar($exploracion->getAttribute($campo)))
            ->values()->all();
        DB::transaction(function () use ($exploracion, $auditoria, $accion, $cambiados, $request) {
            $exploracion->save();

            $auditoria->registrar($accion, $exploracion, datosNuevos: [
                'id_paciente' => $exploracion->id_paciente,
                'campos_modificados' => $cambiados,
            ], request: $request);
        });
    }
}

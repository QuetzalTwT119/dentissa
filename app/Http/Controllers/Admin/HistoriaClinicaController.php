<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

/**
 * Cuestionario de historia clínica (anamnesis), uno por paciente (22/11/2026).
 * Vive en el expediente (📋 Historial) y lo llenan la doctora y el personal con
 * el módulo "pacientes" (config/permisos.php). Control de actividades guarda
 * solo QUÉ campos cambiaron, nunca su contenido (datos de salud).
 */
class HistoriaClinicaController extends Controller
{
    private const LISTAS = [
        'antecedentes_familiares' => HistoriaClinica::ANTECEDENTES_FAMILIARES,
        'antecedentes_personales' => HistoriaClinica::ANTECEDENTES_PERSONALES,
        'alergias' => HistoriaClinica::ALERGIAS,
        'antecedentes_dentales' => HistoriaClinica::ANTECEDENTES_DENTALES,
        'habitos' => HistoriaClinica::HABITOS,
    ];

    public function edit(Paciente $paciente): View
    {
        return view('admin.historia-clinica.edit', [
            'paciente' => $paciente,
            'historia' => $paciente->historiaClinica ?? new HistoriaClinica,
        ]);
    }

    public function update(Request $request, Paciente $paciente, AuditoriaService $auditoria): RedirectResponse
    {
        $reglas = [
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'sexo' => ['nullable', Rule::in(array_keys(HistoriaClinica::SEXOS))],
            'ocupacion' => ['nullable', 'string', 'max:100'],
            'tipo_sangre' => ['nullable', Rule::in(HistoriaClinica::TIPOS_SANGRE)],
            'alergias_otras' => ['nullable', 'string', 'max:500'],
            'medicamentos_actuales' => ['nullable', 'string', 'max:1000'],
            'embarazo' => ['nullable', 'boolean'],
            'lactancia' => ['nullable', 'boolean'],
            'ultima_visita_dentista' => ['nullable', 'string', 'max:100'],
            'antecedentes_familiares_notas' => ['nullable', 'string', 'max:1000'],
            'antecedentes_personales_notas' => ['nullable', 'string', 'max:1000'],
            'antecedentes_dentales_notas' => ['nullable', 'string', 'max:1000'],
            'habitos_notas' => ['nullable', 'string', 'max:1000'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
        foreach (self::LISTAS as $campo => $opciones) {
            $reglas[$campo] = ['nullable', 'array'];
            $reglas[$campo.'.*'] = [Rule::in(array_keys($opciones))];
        }

        $datos = $request->validate($reglas, [
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
        ]);

        // El formulario reemplaza la historia completa: un campo que no llega queda vacío
        // (así borrar un texto o desmarcar todo sí se guarda).
        foreach (array_keys($reglas) as $campo) {
            if (! str_contains($campo, '.') && ! array_key_exists($campo, $datos)) {
                $datos[$campo] = null;
            }
        }
        // Listas sin nada marcado = vacías (el navegador no manda casillas desmarcadas).
        foreach (array_keys(self::LISTAS) as $campo) {
            $datos[$campo] = array_values(array_unique($datos[$campo] ?? []));
        }
        // Embarazo y lactancia solo aplican a mujeres.
        if (($datos['sexo'] ?? null) !== 'femenino') {
            $datos['embarazo'] = null;
            $datos['lactancia'] = null;
        } else {
            $datos['embarazo'] = $request->boolean('embarazo');
            $datos['lactancia'] = $request->boolean('lactancia');
        }
        $datos['actualizado_por'] = $request->user()->id;

        DB::transaction(function () use ($paciente, $datos, $request, $auditoria) {
            $historia = $paciente->historiaClinica ?? new HistoriaClinica(['id_paciente' => $paciente->id]);
            $nueva = ! $historia->exists;
            $historia->fill($datos);
            $cambiados = array_values(array_diff(array_keys($historia->getDirty()), ['actualizado_por', 'id_paciente']));
            $historia->save();

            $auditoria->registrar($nueva ? 'historia_clinica.creada' : 'historia_clinica.actualizada', $historia, datosNuevos: [
                'id_paciente' => $paciente->id,
                'campos_modificados' => $cambiados,
            ], request: $request);
        });

        return Redirect::route('admin.pacientes.historial.index', $paciente)
            ->with('success', "Historia clínica de {$paciente->nombre_completo} guardada.");
    }
}

<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\HorarioAtencion;
use App\Support\Dev\CasosExtremos;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * SOLO DESARROLLO (Fase 4 del pulido): dibuja las pantallas reales con datos
 * "peor caso" armados en memoria, con un interruptor para comparar conjuntos.
 * La ruta solo existe cuando APP_ENV=local; nada se guarda en la base.
 */
class CasosExtremosController extends Controller
{
    public const PANTALLAS = [
        'agenda' => 'Agenda',
        'pacientes' => 'Pacientes',
        'agendar' => 'Agendar cita',
        'confirmacion' => 'Confirmación',
        'dashboard' => 'Panel',
    ];

    public function __invoke(Request $request, string $pantalla): Response
    {
        abort_unless(array_key_exists($pantalla, self::PANTALLAS), 404);

        $conjunto = CasosExtremos::conjunto($request->query('datos'));

        // Usuario del personal en memoria (no inicia sesión ni toca la base).
        Auth::setUser(CasosExtremos::usuario($conjunto));

        $vista = match ($pantalla) {
            'agenda' => view('citas.index', [
                'citas' => CasosExtremos::citas($conjunto),
                'filtros' => [],
            ]),
            'pacientes' => view('admin.pacientes.index', [
                'pacientes' => CasosExtremos::pacientes($conjunto),
                'filtros' => [],
            ]),
            'agendar' => view('citas-publicas.create', [
                'servicios' => CasosExtremos::servicios($conjunto),
                'horarioRegular' => HorarioAtencion::resumenSemanal(),
            ]),
            'confirmacion' => (function () use ($conjunto) {
                session()->now('success', 'Tu cita quedó confirmada. Te esperamos.');
                session()->now('cita_resumen', CasosExtremos::resumenConfirmacion($conjunto));

                return view('citas-publicas.create', [
                    'servicios' => collect(),
                    'horarioRegular' => HorarioAtencion::resumenSemanal(),
                ]);
            })(),
            'dashboard' => view('dashboard'),
        };

        $html = $vista->render();
        $interruptor = view('dev.casos-extremos-interruptor', [
            'pantallaActual' => $pantalla,
            'conjuntoActual' => $conjunto,
            'pantallas' => self::PANTALLAS,
            'conjuntos' => CasosExtremos::CONJUNTOS,
        ])->render();

        return response(str_replace('</body>', $interruptor.'</body>', $html));
    }
}

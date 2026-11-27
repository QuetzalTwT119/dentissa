<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloqueoAgenda;
use App\Models\DatosConsultorio;
use App\Models\HorarioAtencion;
use Illuminate\Contracts\View\View;

/**
 * Configuración (22/11/2026): una sola página con todos los ajustes del
 * consultorio, uno debajo del otro (Datos del consultorio, Horarios y Días u horas
 * bloqueados). Cada bloque se guarda por separado: DatosConsultorioController,
 * HorarioController y BloqueoAgendaController.
 */
class ConfiguracionController extends Controller
{
    /** Próximos bloqueos por página (25/11/2026). */
    public const BLOQUEOS_POR_PAGINA = 5;

    private const ORDEN_SEMANA = [1, 2, 3, 4, 5, 6, 0];

    public function index(): View
    {
        $contacto = config('dentissa.contacto');
        $telefono = preg_replace('/\D/', '', (string) $contacto['telefono']);
        $whatsapp = preg_replace('/^52(?=\d{10}$)/', '', (string) $contacto['whatsapp']);
        $horarios = HorarioAtencion::all()->keyBy('dia_semana');

        return view('admin.configuracion.index', [
            // Con espacios, como se lee: 755 113 1833.
            'telefono' => DatosConsultorio::formatear($telefono),
            'whatsapp' => DatosConsultorio::formatear($whatsapp),
            'mismoWhatsapp' => $whatsapp === $telefono,
            'correo' => $contacto['correo'],
            'direccion' => $contacto['direccion'],
            'nombreMaps' => $contacto['nombre_maps'] ?? '',
            'mision' => $contacto['mision'] ?? '',
            'vision' => $contacto['vision'] ?? '',
            'cofepris' => $contacto['cofepris'] ?? '',
            'redes' => ['instagram' => $contacto['instagram'] ?? '', 'facebook' => $contacto['facebook'] ?? '', 'tiktok' => $contacto['tiktok'] ?? ''],
            // 26/11/2026: cédulas profesionales (antes en Mi perfil).
            'doctores' => CedulaProfesionalController::doctores(),
            'horarios' => collect(self::ORDEN_SEMANA)->map(fn (int $dia) => $horarios->get($dia))->values(),
            // 25/11/2026: paginados (con los festivos eran más de 15 y alargaban la página).
            'bloqueos' => BloqueoAgenda::vigentes()->with('creadoPor:id,nombre,apellido')
                ->paginate(self::BLOQUEOS_POR_PAGINA, ['*'], 'bloqueos')
                ->withQueryString()
                ->fragment('bloqueos'),
        ]);
    }
}

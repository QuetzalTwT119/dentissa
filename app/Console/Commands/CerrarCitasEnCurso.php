<?php

namespace App\Console\Commands;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Services\NotificacionInternaService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cierra las citas que se quedaron «En curso» de días anteriores (25/11/2026).
 *
 * Una cita solo pasa a «En curso» cuando alguien presiona Iniciar con el
 * paciente presente, así que si al día siguiente sigue así es porque se
 * atendió y nadie presionó Completar. Antes la agenda solo mostraba un aviso
 * amarillo y había que cerrarlas a mano; mientras tanto el día contaba mal en
 * Reportes y en la campana.
 *
 * - Solo toca citas de días anteriores: las de hoy las cierra la doctora.
 * - Se puede correr las veces que sea; sin pendientes no hace nada.
 * - Avisa en la campana cuántas cerró, para que no pase en silencio.
 *
 * Corre al iniciar en Railway (start-production.sh) y cada día a las 00:05.
 *
 *   php artisan dentissa:cerrar-citas-en-curso
 *   php artisan dentissa:cerrar-citas-en-curso --simular
 */
class CerrarCitasEnCurso extends Command
{
    protected $signature = 'dentissa:cerrar-citas-en-curso
        {--simular : Muestra qué cerraría sin guardar nada}';

    protected $description = 'Marca como completadas las citas que se quedaron «En curso» de días anteriores';

    public function handle(NotificacionInternaService $notificaciones): int
    {
        $pendientes = Cita::with('paciente:id,nombre,apellido')
            ->where('estado', EstadoCita::EnCurso)
            ->whereDate('fecha_hora', '<', Carbon::today())
            ->orderBy('fecha_hora')
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info('No hay citas «En curso» de días anteriores.');

            return self::SUCCESS;
        }

        foreach ($pendientes as $cita) {
            $this->line('· '.$cita->fecha_hora->format('d/m/Y H:i').' '.trim(($cita->paciente?->nombre ?? '').' '.($cita->paciente?->apellido ?? '')));
        }

        if ($this->option('simular')) {
            $this->info("Se cerrarían {$pendientes->count()} citas. No se guardó nada.");

            return self::SUCCESS;
        }

        // Se vuelve a filtrar por estado dentro del update: si la doctora la
        // completó o la marcó «No asistió» en ese momento, no se pisa.
        $cerradas = DB::transaction(fn () => Cita::whereKey($pendientes->modelKeys())
            ->where('estado', EstadoCita::EnCurso)
            ->update(['estado' => EstadoCita::Completada, 'updated_at' => now()]));

        if ($cerradas > 0) {
            $dias = $pendientes
                ->groupBy(fn (Cita $cita) => $cita->fecha_hora->toDateString())
                ->map(fn ($grupo, $fecha) => Carbon::parse($fecha)->locale('es')->translatedFormat('D j M').' ('.$grupo->count().')')
                ->join(', ');

            $notificaciones->alertaSistema(
                'citas_en_curso_cerradas',
                $cerradas === 1 ? 'Se completó una cita olvidada' : "Se completaron {$cerradas} citas olvidadas",
                // notificaciones_sistema.mensaje es VARCHAR(255) en MySQL.
                Str::limit(
                    'Quedaron «En curso» y nadie presionó Completar: '.$dias
                        .'. Se marcaron como completadas; revisa que tengan su consulta en el historial.',
                    254,
                ),
            );
        }

        $this->info("Se completaron {$cerradas} citas «En curso» de días anteriores.");

        return self::SUCCESS;
    }
}

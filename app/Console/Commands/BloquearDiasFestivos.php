<?php

namespace App\Console\Commands;

use App\Enums\EstadoCita;
use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Models\FestivoOmitido;
use App\Services\NotificacionInternaService;
use App\Support\DiasFestivos;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bloquea los días festivos (App\Support\DiasFestivos) para que no se ofrezcan
 * en la agenda en línea ni en el panel. Se puede correr las veces que sea:
 *
 * - no duplica: si el día ya tiene un bloqueo de día completo, lo salta;
 * - no toca fechas pasadas;
 * - respeta los festivos que la doctora quitó con 🗑 (FestivoOmitido);
 * - no bloquea un día con citas activas (igual que Configuración): lo reporta
 *   para que la doctora decida si las reagenda o abre ese día.
 *
 * Corre al iniciar en Railway (start-production.sh) y el día 1 de cada mes con
 * --avisar, que manda a la campana los festivos que quedaron sin bloquear.
 *
 *   php artisan dentissa:bloquear-festivos            (año actual y siguiente)
 *   php artisan dentissa:bloquear-festivos 2028
 *   php artisan dentissa:bloquear-festivos --simular  (solo muestra qué haría)
 */
class BloquearDiasFestivos extends Command
{
    protected $signature = 'dentissa:bloquear-festivos
        {anios?* : Años a bloquear; si no se indica, el actual y el siguiente}
        {--simular : Muestra qué haría sin guardar nada}
        {--avisar : Avisa en la campana si un festivo no se bloqueó por tener citas}';

    protected $description = 'Bloquea en la agenda los días festivos en que el consultorio no trabaja';

    public function handle(NotificacionInternaService $notificaciones): int
    {
        $hoy = now()->toDateString();
        $anios = $this->anios();
        if ($anios === null) {
            return self::FAILURE;
        }

        $creados = 0;
        $yaEstaban = 0;
        $omitidos = 0;
        $conCitas = [];

        foreach ($anios as $anio) {
            foreach (DiasFestivos::delAnio($anio) as $fecha => $nombre) {
                if ($fecha < $hoy) {
                    continue;
                }

                $etiqueta = Carbon::parse($fecha)->locale('es')->translatedFormat('D j M Y').' · '.$nombre;

                if ($this->yaBloqueado($fecha)) {
                    $yaEstaban++;

                    continue;
                }

                // La doctora quitó este festivo: sí va a trabajar ese día.
                if (FestivoOmitido::whereDate('fecha', $fecha)->exists()) {
                    $omitidos++;

                    continue;
                }

                $citas = $this->citasActivas($fecha);
                if ($citas > 0) {
                    $conCitas[] = $etiqueta.' ('.$citas.' '.($citas === 1 ? 'cita activa' : 'citas activas').')';
                    $this->warn("Sin bloquear: {$etiqueta}. Tiene {$citas} ".($citas === 1 ? 'cita activa' : 'citas activas').'.');

                    continue;
                }

                if (! $this->option('simular')) {
                    DB::transaction(fn () => BloqueoAgenda::create([
                        'fecha_inicio' => $fecha,
                        'fecha_fin' => $fecha,
                        'hora_inicio' => null,
                        'hora_fin' => null,
                        'motivo' => BloqueoAgenda::PREFIJO_FESTIVO.$nombre,
                        'creado_por' => null,
                    ]));
                }
                $creados++;
                $this->line(($this->option('simular') ? 'Se bloquearía: ' : 'Bloqueado: ').$etiqueta);
            }
        }

        $verbo = $this->option('simular') ? 'se bloquearían' : 'bloqueados';
        $this->info("Festivos {$verbo}: {$creados}. Ya estaban: {$yaEstaban}. Quitados por la doctora: {$omitidos}. Con citas (sin bloquear): ".count($conCitas).'.');

        if ($conCitas !== [] && $this->option('avisar') && ! $this->option('simular')) {
            $notificaciones->alertaSistema(
                'festivos_con_citas',
                'Festivos sin bloquear por tener citas',
                "Estos días festivos tienen citas activas y siguen abiertos en la agenda:\n- "
                    .implode("\n- ", $conCitas)
                    ."\n\nReagenda o cancela esas citas y el bloqueo se pondrá solo el próximo mes. Si sí vas a trabajar ese día, no hagas nada.",
            );
        }

        return self::SUCCESS;
    }

    /** @return list<int>|null */
    private function anios(): ?array
    {
        $indicados = $this->argument('anios');
        if ($indicados === []) {
            return [now()->year, now()->year + 1];
        }

        $anios = [];
        foreach ($indicados as $anio) {
            if (! ctype_digit((string) $anio) || (int) $anio < 2000 || (int) $anio > 2100) {
                $this->error("«{$anio}» no es un año válido (por ejemplo, 2027).");

                return null;
            }
            $anios[] = (int) $anio;
        }

        return array_values(array_unique($anios));
    }

    private function yaBloqueado(string $fecha): bool
    {
        return BloqueoAgenda::delDia($fecha)->whereNull('hora_inicio')->exists();
    }

    private function citasActivas(string $fecha): int
    {
        return Cita::query()
            ->whereIn('estado', [EstadoCita::Agendada, EstadoCita::Confirmada, EstadoCita::EnCurso])
            ->whereDate('fecha_hora', $fecha)
            ->count();
    }
}

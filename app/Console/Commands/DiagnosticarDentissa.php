<?php

namespace App\Console\Commands;

use App\Services\DiagnosticoService;
use Illuminate\Console\Command;

/**
 * Lista de verificación del servidor en un solo comando. Pensado para
 * Railway (Console del servicio), donde no se ven las variables:
 *
 *   php artisan dentissa:diagnostico --remoto
 *
 * Nunca imprime valores secretos; solo si existen y si funcionan.
 */
class DiagnosticarDentissa extends Command
{
    protected $signature = 'dentissa:diagnostico {--remoto : Comprueba también con Meta y Google que las credenciales funcionen}';

    protected $description = 'Revisa la configuración de Dentissa (sin mostrar secretos) y señala lo que falta';

    private const ICONOS = [
        DiagnosticoService::OK => '✅',
        // ❗ y no ⚠️: la consola de Windows no dibuja ⚠️ y deja un hueco.
        DiagnosticoService::AVISO => '❗',
        DiagnosticoService::ERROR => '❌',
    ];

    public function handle(DiagnosticoService $diagnostico): int
    {
        $resultados = $diagnostico->ejecutar((bool) $this->option('remoto'));

        $area = null;
        foreach ($resultados as $resultado) {
            if ($resultado['area'] !== $area) {
                $area = $resultado['area'];
                $this->newLine();
                $this->line("<options=bold>{$area}</>");
            }
            $this->line('  '.self::ICONOS[$resultado['estado']].' '.$resultado['detalle']);
        }

        $conteo = array_count_values(array_column($resultados, 'estado'));
        $errores = $conteo[DiagnosticoService::ERROR] ?? 0;
        $avisos = $conteo[DiagnosticoService::AVISO] ?? 0;

        $this->newLine();
        $resumen = "Resumen: {$errores} error(es), {$avisos} aviso(s), ".($conteo[DiagnosticoService::OK] ?? 0).' correcto(s).';
        $errores > 0 ? $this->error($resumen) : $this->info($resumen);

        if (! $this->option('remoto')) {
            $this->comment('Agrega --remoto para comprobar con Meta y Google que las credenciales funcionen.');
        }

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }
}

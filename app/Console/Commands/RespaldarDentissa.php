<?php

namespace App\Console\Commands;

use App\Services\NotificacionInternaService;
use App\Services\Respaldo\DestinoRespaldo;
use App\Services\Respaldo\RespaldoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Respaldo diario (03:00, routes/console.php): crea el paquete cifrado, lo
 * sube a Google Drive y aplica la retención. Si algo falla, avisa al
 * administrador: un respaldo que falla en silencio es peor que no tener uno.
 */
class RespaldarDentissa extends Command
{
    protected $signature = 'dentissa:respaldar';

    protected $description = 'Crea un respaldo cifrado de la base y los archivos privados y lo sube a Google Drive';

    public function handle(RespaldoService $respaldos, DestinoRespaldo $destino, NotificacionInternaService $avisos): int
    {
        $respaldo = null;

        try {
            $respaldo = $respaldos->crear();
            $destino->subir($respaldo['ruta'], $respaldo['nombre']);
            $borrados = $this->aplicarRetencion($destino);

            $conteos = $respaldo['manifiesto']['base']['conteos'];
            $this->info("Respaldo {$respaldo['nombre']} subido: {$conteos['pacientes']} pacientes, {$conteos['citas']} citas y "
                .count($respaldo['manifiesto']['archivos']).' archivos. Copias antiguas eliminadas: '.$borrados.'.');
            Log::info('Respaldo de Dentissa completado', ['nombre' => $respaldo['nombre'], 'eliminados' => $borrados]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            // Solo el mensaje: nunca rutas de credenciales ni respuestas completas.
            Log::error('Falló el respaldo de Dentissa', ['error' => $e->getMessage()]);
            $avisos->alertaSistema(
                'respaldo_fallido',
                'Falló el respaldo automático',
                'El respaldo de esta noche no se completó: '.$e->getMessage().' Revisa la configuración y ejecuta php artisan dentissa:respaldar.',
            );
            $this->error('No se completó el respaldo: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            if ($respaldo) {
                $respaldos->limpiar($respaldo['directorio']);
            }
        }
    }

    /**
     * Borra las copias con más días que la retención, pero nunca las más
     * recientes (minimo_conservar), aunque el servidor haya estado detenido.
     */
    private function aplicarRetencion(DestinoRespaldo $destino): int
    {
        $limite = now()->subDays((int) config('dentissa.respaldos.dias_retencion'));
        $minimo = (int) config('dentissa.respaldos.minimo_conservar');
        $borrados = 0;

        foreach (array_slice($destino->listar(), $minimo) as $copia) {
            if ($copia['creado']->lt($limite)) {
                $destino->eliminar($copia['id']);
                $borrados++;
            }
        }

        return $borrados;
    }
}

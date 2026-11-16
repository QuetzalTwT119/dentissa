<?php

namespace App\Console\Commands;

use App\Services\Respaldo\DestinoRespaldo;
use App\Services\Respaldo\RespaldoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

/**
 * Ensayo de recuperación: descarga el respaldo más reciente (o usa un archivo
 * local), lo descifra, comprueba las huellas y carga la base en un lugar
 * aislado. No modifica la base ni los archivos activos.
 */
class RestaurarPruebaRespaldo extends Command
{
    protected $signature = 'dentissa:restaurar-prueba {--archivo= : Ruta de un respaldo .dntb local en lugar del más reciente de Drive}';

    protected $description = 'Comprueba que un respaldo se puede descifrar y restaurar, sin tocar la base activa';

    public function handle(RespaldoService $respaldos, DestinoRespaldo $destino): int
    {
        $descargado = null;

        try {
            $archivo = $this->option('archivo');
            if (! $archivo) {
                $copia = $destino->listar()[0] ?? null;
                if (! $copia) {
                    $this->error('No hay respaldos en Google Drive todavía.');

                    return self::FAILURE;
                }
                $descargado = storage_path('app/respaldos-tmp/descarga-'.Str::uuid().'.dntb');
                File::ensureDirectoryExists(dirname($descargado), 0700);
                $destino->descargar($copia['id'], $descargado);
                $archivo = $descargado;
                $this->line("Probando {$copia['nombre']}…");
            }

            $resultado = $respaldos->restaurarPrueba($archivo);

            $this->table(['Tabla', 'Registros'], collect($resultado['conteos'])->map(fn ($n, $t) => [$t, $n])->values()->all());
            $this->info("Restauración de prueba correcta: respaldo del {$resultado['manifiesto']['creado_en']}, "
                ."{$resultado['archivos']} archivos con huella verificada. La base activa no se modificó.");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('La restauración de prueba falló: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            if ($descargado) {
                File::delete($descargado);
            }
        }
    }
}

<?php

namespace App\Services\Respaldo;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Arma y comprueba los respaldos de Dentissa.
 *
 * Un respaldo es un .zip con la base de datos, los archivos privados
 * (imágenes clínicas y fotos de perfil) y un manifiesto con la huella SHA-256
 * de cada archivo y los conteos de las tablas clave. El .zip se cifra antes de
 * salir del servidor (CifradoRespaldo).
 */
class RespaldoService
{
    public const VERSION_FORMATO = 1;

    public function __construct(
        private VolcadoBaseDatos $volcado,
        private CifradoRespaldo $cifrado,
    ) {}

    /**
     * Crea el respaldo cifrado en un directorio temporal. Quien lo llama debe
     * borrar `directorio` al terminar (limpiar()).
     *
     * @return array{ruta: string, nombre: string, directorio: string, manifiesto: array<string, mixed>}
     */
    public function crear(): array
    {
        $directorio = $this->directorioTemporal();
        $paquete = $directorio.DIRECTORY_SEPARATOR.'paquete';
        File::ensureDirectoryExists($paquete);

        try {
            $base = $this->volcado->exportar($paquete);
            $rutaBase = $paquete.DIRECTORY_SEPARATOR.$base['archivo'];

            $zip = new ZipArchive;
            $rutaZip = $directorio.DIRECTORY_SEPARATOR.'respaldo.zip';
            if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el paquete del respaldo.');
            }

            $zip->addFile($rutaBase, 'base/'.$base['archivo']);
            $archivos = [];
            foreach ($this->archivosPrivados() as $relativa => $absoluta) {
                $zip->addFile($absoluta, 'archivos/'.$relativa);
                $archivos[] = ['ruta' => $relativa, 'sha256' => hash_file('sha256', $absoluta), 'bytes' => filesize($absoluta)];
            }

            $manifiesto = [
                'formato' => self::VERSION_FORMATO,
                'creado_en' => now()->toIso8601String(),
                'aplicacion' => config('app.name'),
                'base' => [
                    'motor' => $base['motor'],
                    'archivo' => $base['archivo'],
                    'sha256' => hash_file('sha256', $rutaBase),
                    'conteos' => $base['conteos'],
                ],
                'archivos' => $archivos,
            ];
            $zip->addFromString('manifiesto.json', json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $zip->close();

            $nombre = 'dentissa-respaldo-'.now()->format('Ymd-His').'.dntb';
            $rutaCifrada = $directorio.DIRECTORY_SEPARATOR.$nombre;
            $this->cifrado->cifrar($rutaZip, $rutaCifrada);

            // Solo queda el archivo cifrado; el .zip en claro se borra ya.
            File::delete($rutaZip);
            File::deleteDirectory($paquete);

            return ['ruta' => $rutaCifrada, 'nombre' => $nombre, 'directorio' => $directorio, 'manifiesto' => $manifiesto];
        } catch (\Throwable $e) {
            $this->limpiar($directorio);
            throw $e;
        }
    }

    /**
     * Descifra un respaldo, comprueba cada huella y carga la base en un lugar
     * aislado. Nunca toca la base ni los archivos activos.
     *
     * @return array{manifiesto: array<string, mixed>, conteos: array<string, int>, archivos: int}
     */
    public function restaurarPrueba(string $rutaCifrada): array
    {
        $directorio = $this->directorioTemporal();

        try {
            $rutaZip = $directorio.DIRECTORY_SEPARATOR.'respaldo.zip';
            $this->cifrado->descifrar($rutaCifrada, $rutaZip);

            $zip = new ZipArchive;
            if ($zip->open($rutaZip) !== true) {
                throw new RuntimeException('El respaldo descifrado no es un paquete válido.');
            }
            $extraido = $directorio.DIRECTORY_SEPARATOR.'extraido';
            $zip->extractTo($extraido);
            $zip->close();

            $manifiesto = json_decode((string) @file_get_contents($extraido.'/manifiesto.json'), true);
            if (! is_array($manifiesto) || ($manifiesto['formato'] ?? null) !== self::VERSION_FORMATO) {
                throw new RuntimeException('El respaldo no tiene un manifiesto válido.');
            }

            $rutaBase = $extraido.'/base/'.$manifiesto['base']['archivo'];
            $this->verificarHuella($rutaBase, $manifiesto['base']['sha256'], 'la base de datos');
            foreach ($manifiesto['archivos'] as $archivo) {
                $this->verificarHuella($extraido.'/archivos/'.$archivo['ruta'], $archivo['sha256'], $archivo['ruta']);
            }

            $conteos = $this->volcado->restaurarAislado($manifiesto['base']['motor'], $rutaBase, $directorio);
            if ($conteos !== $manifiesto['base']['conteos']) {
                throw new RuntimeException('Los registros restaurados no coinciden con los del respaldo.');
            }

            return ['manifiesto' => $manifiesto, 'conteos' => $conteos, 'archivos' => count($manifiesto['archivos'])];
        } finally {
            $this->limpiar($directorio);
        }
    }

    public function limpiar(string $directorio): void
    {
        File::deleteDirectory($directorio);
    }

    /** @return array<string, string> ruta relativa => ruta absoluta */
    private function archivosPrivados(): array
    {
        $raiz = (string) config('dentissa.respaldos.directorio_archivos');
        if (! is_dir($raiz)) {
            return [];
        }

        $excluir = config('dentissa.respaldos.excluir', []);
        $archivos = [];
        foreach (File::allFiles($raiz) as $archivo) {
            if (in_array($archivo->getFilename(), $excluir, true)) {
                continue;
            }
            $archivos[str_replace('\\', '/', $archivo->getRelativePathname())] = $archivo->getPathname();
        }
        ksort($archivos);

        return $archivos;
    }

    private function verificarHuella(string $ruta, string $esperada, string $descripcion): void
    {
        if (! is_file($ruta) || ! hash_equals($esperada, hash_file('sha256', $ruta))) {
            throw new RuntimeException("La huella de {$descripcion} no coincide con el manifiesto.");
        }
    }

    private function directorioTemporal(): string
    {
        // Fuera de storage/app/private: nunca se incluye a sí mismo.
        $directorio = storage_path('app/respaldos-tmp/'.Str::uuid());
        File::ensureDirectoryExists($directorio, 0700);

        return $directorio;
    }
}

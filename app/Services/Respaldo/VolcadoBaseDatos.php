<?php

namespace App\Services\Respaldo;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

/**
 * Exporta la base activa a un archivo y la carga en una base aislada para
 * comprobar el respaldo. Nunca escribe en la base activa.
 *
 * - SQLite: `VACUUM INTO`, una copia consistente aunque haya escrituras.
 * - MySQL: volcado SQL hecho con PDO dentro de una transacción con instantánea
 *   consistente. No depende de mysqldump: el cliente de Debian es de MariaDB y
 *   puede fallar al autenticarse contra MySQL 8.
 */
class VolcadoBaseDatos
{
    /** Tablas que se cuentan en el manifiesto y se comparan al restaurar. */
    public const TABLAS_CLAVE = ['users', 'pacientes', 'citas', 'historial_clinico', 'imagenes'];

    /** Datos efímeros o sensibles sin valor de recuperación: solo su estructura. */
    private const SIN_DATOS = ['cache', 'cache_locks', 'sessions', 'jobs', 'failed_jobs', 'job_batches', 'password_reset_tokens'];

    /**
     * @return array{motor: string, archivo: string, conteos: array<string, int>}
     */
    public function exportar(string $directorio): array
    {
        $conexion = DB::connection();
        $motor = $conexion->getDriverName();

        return match ($motor) {
            'sqlite' => $this->exportarSqlite($conexion, $directorio),
            'mysql', 'mariadb' => $this->exportarMysql($conexion, $directorio),
            default => throw new RuntimeException("El respaldo no admite el motor de base de datos {$motor}."),
        };
    }

    /**
     * Carga el volcado en una base aislada y devuelve los conteos de las tablas clave.
     *
     * @return array<string, int>
     */
    public function restaurarAislado(string $motor, string $archivo, string $directorioTrabajo): array
    {
        if ($motor === 'sqlite') {
            // El archivo ya es una base completa: se abre aparte, sin tocar la activa.
            $pdo = new PDO('sqlite:'.$archivo);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $integridad = $pdo->query('PRAGMA integrity_check')->fetchColumn();
            if ($integridad !== 'ok') {
                throw new RuntimeException('La base restaurada no pasó la revisión de integridad de SQLite.');
            }

            return $this->contar($pdo);
        }

        return $this->restaurarMysqlAislado($archivo);
    }

    /** @return array{motor: string, archivo: string, conteos: array<string, int>} */
    private function exportarSqlite(Connection $conexion, string $directorio): array
    {
        $destino = $directorio.DIRECTORY_SEPARATOR.'base.sqlite';
        // VACUUM INTO no admite parámetros; la ruta es interna y se escapa.
        $conexion->statement("VACUUM INTO '".str_replace("'", "''", $destino)."'");

        return ['motor' => 'sqlite', 'archivo' => 'base.sqlite', 'conteos' => $this->contar($conexion->getPdo())];
    }

    /** @return array{motor: string, archivo: string, conteos: array<string, int>} */
    private function exportarMysql(Connection $conexion, string $directorio): array
    {
        $pdo = $conexion->getPdo();
        $destino = $directorio.DIRECTORY_SEPARATOR.'base.sql';
        $salida = fopen($destino, 'wb');

        try {
            $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');

            // Una sentencia por línea: así la restauración las lee sin un parser SQL.
            fwrite($salida, "SET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n");

            $tablas = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tablas as $tabla) {
                $nombre = '`'.str_replace('`', '``', $tabla).'`';
                $crear = $pdo->query("SHOW CREATE TABLE {$nombre}")->fetch(PDO::FETCH_NUM)[1];
                fwrite($salida, "DROP TABLE IF EXISTS {$nombre};\n".preg_replace('/\s*\R\s*/', ' ', $crear).";\n");

                if (in_array($tabla, self::SIN_DATOS, true)) {
                    continue;
                }

                $filas = $pdo->query("SELECT * FROM {$nombre}", PDO::FETCH_NUM);
                foreach ($filas as $fila) {
                    $valores = array_map(fn ($valor) => $valor === null ? 'NULL' : $pdo->quote((string) $valor), $fila);
                    fwrite($salida, "INSERT INTO {$nombre} VALUES (".implode(',', $valores).");\n");
                }
            }

            fwrite($salida, "SET FOREIGN_KEY_CHECKS=1;\n");
            $conteos = $this->contar($pdo);
            $pdo->exec('COMMIT');
        } catch (\Throwable $e) {
            $pdo->exec('ROLLBACK');
            throw $e;
        } finally {
            fclose($salida);
        }

        return ['motor' => 'mysql', 'archivo' => 'base.sql', 'conteos' => $conteos];
    }

    /** @return array<string, int> */
    private function restaurarMysqlAislado(string $archivo): array
    {
        $config = DB::connection()->getConfig();
        $esquema = $config['database'].'_restauracion_prueba';

        if ($esquema === $config['database'] || ! preg_match('/^[A-Za-z0-9_]+$/', $esquema)) {
            throw new RuntimeException('El esquema de restauración no es seguro.');
        }

        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        // En producción el usuario suele tener permisos sobre una sola base:
        // la de prueba puede crearse de antemano y aquí solo se reutiliza.
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$esquema}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$esquema}`");
        } catch (\PDOException) {
            throw new RuntimeException(
                "El usuario de MySQL no puede usar la base de prueba {$esquema}. Créala una vez con un usuario administrador: "
                ."CREATE DATABASE {$esquema}; GRANT ALL ON {$esquema}.* TO '{$config['username']}'@'%';"
            );
        }

        $entrada = fopen($archivo, 'rb');
        try {
            $this->vaciar($pdo);
            while (($linea = fgets($entrada)) !== false) {
                if (trim($linea) !== '') {
                    $pdo->exec(rtrim($linea, "\r\n"));
                }
            }
            $conteos = $this->contar($pdo);
        } finally {
            fclose($entrada);
            // La base de prueba solo sirve para comprobar el respaldo: se
            // vacía, pero se conserva para no necesitar permiso de crearla.
            $this->vaciar($pdo);
        }

        return $conteos;
    }

    private function vaciar(PDO $pdo): void
    {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN) as $tabla) {
            $pdo->exec('DROP TABLE IF EXISTS `'.str_replace('`', '``', $tabla).'`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /** @return array<string, int> */
    private function contar(PDO $pdo): array
    {
        $conteos = [];
        foreach (self::TABLAS_CLAVE as $tabla) {
            $conteos[$tabla] = (int) $pdo->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn();
        }

        return $conteos;
    }
}

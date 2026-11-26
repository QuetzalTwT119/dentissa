<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Prueba el enrutador server.php (el que usa `php artisan serve` en producción)
 * sin levantar un servidor: simula la petición y revisa si lo sirve él mismo
 * o si lo pasa a Laravel.
 */
class ServidorArchivosPublicosTest extends TestCase
{
    /**
     * Ejecuta server.php en un proceso aparte y devuelve si entregó el archivo
     * él mismo o si lo pasó a Laravel, y con qué SCRIPT_NAME.
     */
    private function resolver(string $ruta): array
    {
        $raiz = dirname(__DIR__, 2);
        $script = <<<'PHP'
            $_SERVER['REQUEST_URI'] = $argv[1];
            $_SERVER['REQUEST_METHOD'] = 'HEAD';
            $GLOBALS['__paso_a_laravel'] = false;
            // index.php se sustituye por una marca para no arrancar Laravel.
            $codigo = file_get_contents($argv[2].'/server.php');
            $codigo = str_replace("require_once \$publicPath.'/index.php';", "\$GLOBALS['__paso_a_laravel'] = true; return false;", $codigo);
            $sirvio = eval('?>'.str_replace('__DIR__', var_export($argv[2], true), $codigo));
            echo json_encode([
                'sirvio' => $sirvio === true,
                'laravel' => $GLOBALS['__paso_a_laravel'],
                'script' => $_SERVER['SCRIPT_NAME'] ?? null,
            ]);
            PHP;

        // Se guarda en un archivo temporal: pasar código con `php -r` se rompe
        // con las comillas de la terminal de Windows.
        $base = tempnam(sys_get_temp_dir(), 'router');
        $ayudante = $base.'.php';
        file_put_contents($ayudante, "<?php\n".$script);

        try {
            $comando = escapeshellarg(PHP_BINARY).' '.escapeshellarg($ayudante).' '
                .escapeshellarg($ruta).' '.escapeshellarg($raiz);

            return json_decode((string) shell_exec($comando), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            @unlink($ayudante);
            @unlink($base);
        }
    }

    public static function archivosQueDebenServirse(): array
    {
        return [
            'robots' => ['/robots.txt'],
            'manifiesto PWA' => ['/manifest.webmanifest'],
            'página sin conexión' => ['/offline.html'],
            'service worker' => ['/sw.js'],
            'imagen' => ['/images/9.jpg'],
        ];
    }

    #[DataProvider('archivosQueDebenServirse')]
    public function test_sirve_los_archivos_publicos_permitidos(string $ruta): void
    {
        $resultado = $this->resolver($ruta);

        $this->assertTrue($resultado['sirvio'], "server.php debería entregar {$ruta} directamente.");
        $this->assertFalse($resultado['laravel']);
    }

    public static function archivosInternos(): array
    {
        return [
            'htaccess' => ['/.htaccess'],
            'manifiesto de Vite' => ['/build/manifest.json'],
            'archivo inexistente' => ['/no-existe.txt'],
        ];
    }

    #[DataProvider('archivosInternos')]
    public function test_los_archivos_internos_pasan_a_laravel_con_el_script_correcto(string $ruta): void
    {
        $resultado = $this->resolver($ruta);

        $this->assertFalse($resultado['sirvio']);
        $this->assertTrue($resultado['laravel']);
        // Con '/index.php' Laravel calcula bien la ruta y responde 404 en vez de la portada.
        $this->assertSame('/index.php', $resultado['script']);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\User;
use App\Services\Respaldo\CifradoRespaldo;
use App\Services\Respaldo\DestinoRespaldo;
use App\Services\Respaldo\GoogleDriveDestino;
use App\Services\Respaldo\RespaldoService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

/**
 * Fase 2 para pacientes reales: respaldo diario cifrado fuera del servidor
 * y ensayo de restauración. No usa RefreshDatabase porque VACUUM INTO no
 * puede ejecutarse dentro de una transacción: cada prueba migra su propia
 * base :memory: nueva en setUp().
 */
class RespaldoTest extends TestCase
{
    private string $temporal;

    private DestinoRespaldo $destino;

    protected function setUp(): void
    {
        parent::setUp();

        // En el job de MySQL la base persiste entre pruebas: se recrea limpia.
        $this->artisan('migrate:fresh');
        Mail::fake();
        $this->temporal = sys_get_temp_dir().'/dentissa-respaldo-prueba-'.uniqid();
        File::ensureDirectoryExists($this->temporal.'/privado/historial');
        File::put($this->temporal.'/privado/historial/radiografia.jpg', 'imagen-clinica-de-prueba');
        File::put($this->temporal.'/privado/google-oauth-token.json', '{"refresh_token":"secreto-que-no-debe-salir"}');

        config([
            'dentissa.respaldos.clave' => CifradoRespaldo::generarLlave(),
            'dentissa.respaldos.directorio_archivos' => $this->temporal.'/privado',
        ]);

        $this->destino = new DestinoEnMemoria($this->temporal.'/nube');
        $this->app->instance(DestinoRespaldo::class, $this->destino);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporal);
        // Sin transacción que revertir: se deja la base vacía para las demás pruebas.
        $this->artisan('migrate:fresh');

        parent::tearDown();
    }

    public function test_el_respaldo_sube_un_archivo_cifrado_con_base_e_imagenes_pero_sin_credenciales(): void
    {
        Paciente::create(['nombre' => 'Lucía', 'apellido' => 'Marcadora', 'telefono' => '5512345678', 'email' => 'lucia@example.test']);

        $this->artisan('dentissa:respaldar')->assertSuccessful();

        $copias = $this->destino->listar();
        $this->assertCount(1, $copias);
        $this->assertMatchesRegularExpression('/^dentissa-respaldo-\d{8}-\d{6}\.dntb$/', $copias[0]['nombre']);

        // Cifrado de verdad: ni el nombre del paciente ni la imagen se leen en claro.
        $contenido = File::get($this->destino->ruta($copias[0]['id']));
        $this->assertStringNotContainsString('Marcadora', $contenido);
        $this->assertStringNotContainsString('imagen-clinica-de-prueba', $contenido);

        $resultado = $this->restaurar($copias[0]['id']);
        $this->assertSame(1, $resultado['manifiesto']['base']['conteos']['pacientes']);
        $rutas = array_column($resultado['manifiesto']['archivos'], 'ruta');
        $this->assertSame(['historial/radiografia.jpg'], $rutas);
        $this->assertNotContains('google-oauth-token.json', $rutas);
    }

    public function test_la_restauracion_de_prueba_verifica_huellas_y_no_toca_la_base_activa(): void
    {
        Paciente::create(['nombre' => 'Ana', 'apellido' => 'Prueba', 'telefono' => '5512345678', 'email' => 'ana@example.test']);
        $this->artisan('dentissa:respaldar')->assertSuccessful();
        Paciente::create(['nombre' => 'Beto', 'apellido' => 'Posterior', 'telefono' => '5587654321', 'email' => 'beto@example.test']);

        $this->artisan('dentissa:restaurar-prueba')
            ->expectsOutputToContain('Restauración de prueba correcta')
            ->assertSuccessful();

        // El respaldo tenía 1 paciente; la base activa conserva los 2.
        $this->assertSame(2, Paciente::count());
    }

    public function test_un_respaldo_alterado_o_con_otra_llave_se_rechaza(): void
    {
        $this->artisan('dentissa:respaldar')->assertSuccessful();
        $ruta = $this->destino->ruta($this->destino->listar()[0]['id']);

        $alterado = $this->temporal.'/alterado.dntb';
        $bytes = File::get($ruta);
        $bytes[strlen($bytes) - 20] = $bytes[strlen($bytes) - 20] === 'A' ? 'B' : 'A';
        File::put($alterado, $bytes);
        $this->artisan('dentissa:restaurar-prueba', ['--archivo' => $alterado])
            ->expectsOutputToContain('la llave no coincide o el archivo fue alterado')
            ->assertFailed();

        config(['dentissa.respaldos.clave' => CifradoRespaldo::generarLlave()]);
        $this->artisan('dentissa:restaurar-prueba', ['--archivo' => $ruta])->assertFailed();
    }

    public function test_la_retencion_borra_copias_viejas_pero_conserva_las_tres_mas_recientes(): void
    {
        Carbon::setTestNow('2030-03-31 03:00');

        try {
            foreach ([10, 45, 60, 90] as $dias) {
                $this->destino->agregar("vieja-{$dias}.dntb", now()->subDays($dias));
            }

            $this->artisan('dentissa:respaldar')->assertSuccessful();
        } finally {
            Carbon::setTestNow();
        }

        // Nueva + 10 días + 45 días (mínimo de 3, aunque tenga más de 30 días).
        $nombres = array_column($this->destino->listar(), 'nombre');
        $this->assertCount(3, $nombres);
        $this->assertContains('vieja-10.dntb', $nombres);
        $this->assertContains('vieja-45.dntb', $nombres);
        $this->assertNotContains('vieja-60.dntb', $nombres);
        $this->assertNotContains('vieja-90.dntb', $nombres);
    }

    public function test_si_el_respaldo_falla_avisa_al_administrador(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente, 'activo' => true]);
        config(['dentissa.respaldos.clave' => null]);

        $this->artisan('dentissa:respaldar')
            ->expectsOutputToContain('Falta DENTISSA_RESPALDO_CLAVE')
            ->assertFailed();

        $this->assertTrue(NotificacionSistema::where('user_id', $admin->id)->where('evento', 'respaldo_fallido')->exists());
        $this->assertFalse(NotificacionSistema::where('user_id', $asistente->id)->exists());
        $this->assertSame([], $this->destino->listar());
        // No quedan archivos temporales en el servidor.
        $this->assertSame([], File::glob(storage_path('app/respaldos-tmp/*/respaldo.zip')));
    }

    public function test_el_respaldo_corre_cada_dia_a_las_tres_de_la_manana(): void
    {
        Artisan::call('schedule:list');

        // schedule:list alinea las columnas con espacios ("0    3 * * *").
        $this->assertMatchesRegularExpression('/0\s+3 \* \* \*\s+php artisan dentissa:respaldar/', Artisan::output());
    }

    public function test_google_drive_crea_la_carpeta_y_sube_con_subida_reanudable(): void
    {
        Cache::flush();
        config(['services.gmail.client_id' => 'id', 'services.gmail.client_secret' => 'secreto', 'services.gmail.refresh_token' => 'refresco']);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'token-de-prueba']),
            'www.googleapis.com/drive/v3/files?*' => Http::sequence()
                ->push(['files' => []])
                ->push(['files' => [['id' => 'archivo-1', 'name' => 'r.dntb', 'createdTime' => '2030-01-01T03:00:00Z', 'size' => '42']]]),
            'www.googleapis.com/drive/v3/files' => Http::response(['id' => 'carpeta-1']),
            'www.googleapis.com/upload/drive/v3/files?uploadType=resumable' => Http::response([], 200, ['Location' => 'https://www.googleapis.com/upload/sesion-1']),
            'www.googleapis.com/upload/sesion-1' => Http::response(['id' => 'archivo-1']),
        ]);
        $archivo = $this->temporal.'/r.dntb';
        File::put($archivo, 'contenido-cifrado');

        $drive = app(GoogleDriveDestino::class);
        $this->assertSame('archivo-1', $drive->subir($archivo, 'r.dntb'));
        $this->assertSame(42, $drive->listar()[0]['bytes']);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'uploadType=resumable')
            && $r['parents'] === ['carpeta-1']
            && $r->hasHeader('Authorization', 'Bearer token-de-prueba'));
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r->body() === 'contenido-cifrado');
    }

    public function test_si_falta_el_permiso_de_drive_el_error_lo_explica(): void
    {
        Cache::flush();
        config(['services.gmail.client_id' => 'id', 'services.gmail.client_secret' => 'secreto', 'services.gmail.refresh_token' => 'refresco']);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'token-de-prueba']),
            'www.googleapis.com/*' => Http::response(['error' => ['message' => 'Insufficient Permission']], 403),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('permiso de Drive');

        app(GoogleDriveDestino::class)->listar();
    }

    /** @return array<string, mixed> */
    private function restaurar(string $id): array
    {
        $ruta = $this->temporal.'/descarga.dntb';
        $this->destino->descargar($id, $ruta);

        return app(RespaldoService::class)->restaurarPrueba($ruta);
    }
}

/** Destino de respaldos en una carpeta temporal, para no llamar a Google. */
class DestinoEnMemoria implements DestinoRespaldo
{
    /** @var array<string, array{id: string, nombre: string, creado: Carbon, bytes: int}> */
    private array $copias = [];

    public function __construct(private string $carpeta)
    {
        File::ensureDirectoryExists($carpeta);
    }

    public function subir(string $rutaLocal, string $nombre): string
    {
        return $this->agregar($nombre, now(), $rutaLocal);
    }

    public function agregar(string $nombre, Carbon $creado, ?string $rutaLocal = null): string
    {
        $id = 'copia-'.(count($this->copias) + 1);
        $rutaLocal ? File::copy($rutaLocal, $this->ruta($id)) : File::put($this->ruta($id), 'x');
        $this->copias[$id] = ['id' => $id, 'nombre' => $nombre, 'creado' => $creado->copy(), 'bytes' => filesize($this->ruta($id))];

        return $id;
    }

    public function listar(): array
    {
        $copias = array_values($this->copias);
        usort($copias, fn ($a, $b) => $b['creado'] <=> $a['creado']);

        return $copias;
    }

    public function descargar(string $id, string $rutaLocal): void
    {
        File::copy($this->ruta($id), $rutaLocal);
    }

    public function eliminar(string $id): void
    {
        File::delete($this->ruta($id));
        unset($this->copias[$id]);
    }

    public function ruta(string $id): string
    {
        return $this->carpeta.'/'.$id;
    }
}

<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use App\Services\DiagnosticoService;
use App\Services\Respaldo\CifradoRespaldo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * El diagnóstico revela qué falta configurar, pero nunca el valor de un secreto.
 */
class DiagnosticoTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETOS = [
        'token-whatsapp-super-secreto',
        'clave-secreta-app-meta',
        'frase-de-verificacion-privada',
        'refresh-token-google-privado',
        'client-secret-google-privado',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.name' => 'Dentissa',
            'dentissa.respaldos.clave' => CifradoRespaldo::generarLlave(),
            'dentissa.whatsapp.phone_number_id' => '1297075516831399',
            'dentissa.whatsapp.access_token' => self::SECRETOS[0],
            'dentissa.whatsapp.app_secret' => self::SECRETOS[1],
            'dentissa.whatsapp.verify_token' => self::SECRETOS[2],
            'services.gmail.client_id' => 'cliente-google',
            'services.gmail.client_secret' => self::SECRETOS[4],
            'services.gmail.refresh_token' => self::SECRETOS[3],
            'dentissa.privacidad.responsable' => 'Clínica de Prueba',
            'dentissa.privacidad.domicilio' => 'Calle 1',
            'dentissa.privacidad.correo' => 'privacidad@example.test',
            'dentissa.privacidad.revisado' => true,
            'dentissa.seguridad.dos_pasos_admin_obligatorio' => true,
        ]);
    }

    public function test_una_configuracion_completa_pasa_sin_errores(): void
    {
        $resultados = app(DiagnosticoService::class)->ejecutar();

        $this->assertSame([], $this->conEstado($resultados, DiagnosticoService::ERROR));
        $this->assertContains('Número, token, clave secreta y token de verificación configurados.', array_column($resultados, 'detalle'));
    }

    public function test_senala_lo_que_falta_y_el_comando_termina_con_error(): void
    {
        config([
            'dentissa.respaldos.clave' => null,
            'dentissa.whatsapp.app_secret' => null,
            'dentissa.privacidad.responsable' => null,
            'dentissa.seguridad.dos_pasos_admin_obligatorio' => false,
        ]);

        $this->artisan('dentissa:diagnostico')
            ->expectsOutputToContain('DENTISSA_RESPALDO_CLAVE falta o no es válida')
            ->expectsOutputToContain('Faltan variables: DENTISSA_WHATSAPP_APP_SECRET.')
            ->expectsOutputToContain('nombre del responsable')
            ->expectsOutputToContain('DENTISSA_2FA_ADMIN=false')
            ->assertFailed();
    }

    public function test_avisa_si_un_administrador_no_ha_activado_la_verificacion(): void
    {
        User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);

        $detalles = array_column(app(DiagnosticoService::class)->ejecutar(), 'detalle');

        $this->assertContains('1 administrador(es) aún no activan la verificación en dos pasos; se les pedirá al entrar.', $detalles);
    }

    public function test_la_revision_remota_confirma_meta_y_google_y_detecta_el_numero_del_bot(): void
    {
        Cache::flush();
        config(['dentissa.contacto.telefono' => '755 102 0089']);
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'display_phone_number' => '+52 1 755 102 0089', 'verified_name' => 'Dentissa', 'status' => 'CONNECTED', 'quality_rating' => 'GREEN',
            ]),
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'token-temporal-google']),
            'oauth2.googleapis.com/tokeninfo*' => Http::response(['scope' => 'https://www.googleapis.com/auth/gmail.send']),
        ]);

        $resultados = app(DiagnosticoService::class)->ejecutar(remoto: true);
        $detalles = array_column($resultados, 'detalle');

        $this->assertTrue(collect($detalles)->contains(fn ($d) => str_contains($d, 'El token funciona') && str_contains($d, 'CONNECTED')));
        $this->assertTrue(collect($detalles)->contains(fn ($d) => str_contains($d, 'mismo número del bot')));
        $this->assertTrue(collect($detalles)->contains(fn ($d) => str_contains($d, 'Falta el permiso de Drive')));
    }

    public function test_si_meta_rechaza_el_token_lo_dice_sin_mostrarlo(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['code' => 190, 'message' => 'Error validating access token']], 401)]);

        $resultados = app(DiagnosticoService::class)->ejecutar(remoto: true);

        $this->assertTrue(collect($resultados)->contains(fn ($r) => $r['estado'] === DiagnosticoService::ERROR && str_contains($r['detalle'], 'código 190')));
    }

    public function test_nunca_imprime_un_secreto(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['display_phone_number' => '+52 1 755 000 0000', 'verified_name' => 'Dentissa', 'status' => 'CONNECTED']),
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'token-temporal-google']),
            'oauth2.googleapis.com/tokeninfo*' => Http::response(['scope' => 'https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/drive.file']),
            'www.googleapis.com/*' => Http::response(['files' => []]),
        ]);

        Artisan::call('dentissa:diagnostico', ['--remoto' => true]);
        $salida = Artisan::output();

        foreach ([...self::SECRETOS, config('dentissa.respaldos.clave'), 'token-temporal-google'] as $secreto) {
            $this->assertStringNotContainsString($secreto, $salida);
        }
        $this->assertStringContainsString('Resumen:', $salida);
    }

    /** @return list<array{area: string, estado: string, detalle: string}> */
    private function conEstado(array $resultados, string $estado): array
    {
        return array_values(array_filter($resultados, fn ($r) => $r['estado'] === $estado));
    }
}

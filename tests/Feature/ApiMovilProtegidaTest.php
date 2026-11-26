<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * API de la app móvil y del ESP32 protegida (26/11/2026). Antes (PR #80 y los
 * commits del 25/11) no pedía clave: cualquiera podía abrir la puerta.
 *
 * Criterios de aceptación:
 * - Ninguna ruta api/* queda sin protección, salvo el login (con límite).
 * - La app necesita el token del login; sin él, o vencido, 401.
 * - El ESP32 necesita X-Dispositivo-Clave; sin clave configurada, 503.
 * - El login entrega un token que se guarda solo como huella; cerrar sesión
 *   lo invalida; una cuenta desactivada no entra.
 * - Más de 5 intentos de login por minuto se bloquean.
 */
class ApiMovilProtegidaTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'clave-de-prueba-del-esp32';

    protected function setUp(): void
    {
        parent::setUp();
        config(['dentissa.iot.clave_dispositivo' => self::CLAVE]);
        Cache::flush();
    }

    public function test_toda_ruta_api_tiene_proteccion(): void
    {
        $sinProteccion = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta) => str_starts_with($ruta->uri(), 'api/'))
            ->reject(fn ($ruta) => array_intersect(['app.token', 'iot.dispositivo', 'throttle:app-login'], $ruta->gatherMiddleware()) !== [])
            ->map(fn ($ruta) => implode('|', $ruta->methods()).' '.$ruta->uri())
            ->values()
            ->all();

        $this->assertSame([], $sinProteccion, 'Rutas de la API sin token, clave ni límite.');
    }

    public function test_sin_token_ni_clave_no_se_abre_nada(): void
    {
        $this->postJson('/api/iot/puerta/abrir')->assertUnauthorized();
        $this->postJson('/api/iot/actuadores', ['dispositivo' => 'LUCES', 'encendido' => true])->assertUnauthorized();
        $this->getJson('/api/iot/estado')->assertUnauthorized();
        $this->getJson('/api/iot/estadisticas')->assertUnauthorized();
        $this->getJson('/api/iot/comandos')->assertUnauthorized();
        $this->postJson('/api/iot/estado', ['asientos' => []])->assertUnauthorized();
        $this->getJson('/api/iot/comandos', ['X-Dispositivo-Clave' => 'otra'])->assertUnauthorized();
        $this->postJson('/api/iot/puerta/abrir', [], ['Authorization' => 'Bearer inventado'])->assertUnauthorized();

        $this->assertFalse(Cache::has('iot_abrir_puerta'), 'La puerta no se pidió abrir');
    }

    public function test_la_app_entra_con_token_y_el_esp32_con_su_clave(): void
    {
        $doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'email' => 'doctora@example.test']);

        $login = $this->postJson('/api/app/login', ['email' => 'Doctora@Example.test', 'password' => 'password'])
            ->assertOk()->assertJsonPath('ok', true)->assertJsonPath('user.rol', 'super_admin');
        $token = $login->json('token');
        $this->assertSame(60, strlen($token));
        // En la base solo queda la huella, nunca el token.
        $this->assertSame(hash('sha256', $token), $doctora->fresh()->app_token);
        $this->assertArrayNotHasKey('app_token', $login->json('user'));

        $app = ['Authorization' => 'Bearer '.$token];
        $this->getJson('/api/iot/estado', $app)->assertOk()->assertJsonCount(6, 'asientos');
        $this->postJson('/api/iot/puerta/abrir', [], $app)->assertOk();

        // El ESP32 recoge la orden con su clave (una sola vez).
        $esp32 = ['X-Dispositivo-Clave' => self::CLAVE];
        $this->getJson('/api/iot/comandos', $esp32)->assertOk()->assertJsonPath('abrir_puerta', true);
        $this->getJson('/api/iot/comandos', $esp32)->assertOk()->assertJsonPath('abrir_puerta', false);
        $this->postJson('/api/iot/estado', ['asientos' => [['numero' => 2, 'ocupado' => true]]], $esp32)->assertOk();
        $this->getJson('/api/iot/estado', $app)->assertJsonPath('asientos.1.ocupado', true)->assertJsonPath('esp32_en_linea', true);

        // Cerrar sesión invalida el token.
        $this->postJson('/api/app/logout', [], $app)->assertOk();
        $this->getJson('/api/iot/estado', $app)->assertUnauthorized();
    }

    public function test_token_vencido_o_cuenta_desactivada_no_sirven(): void
    {
        $doctor = User::factory()->create(['rol' => RolUsuario::Doctor, 'email' => 'doctor@example.test']);
        $token = $this->postJson('/api/app/login', ['email' => 'doctor@example.test', 'password' => 'password'])->json('token');
        $app = ['Authorization' => 'Bearer '.$token];

        $this->travel(31)->days();
        $this->getJson('/api/iot/estado', $app)->assertUnauthorized();
        $this->travelBack();

        $doctor->update(['activo' => false]);
        $this->getJson('/api/iot/estado', $app)->assertUnauthorized();
        $this->postJson('/api/app/login', ['email' => 'doctor@example.test', 'password' => 'password'])->assertForbidden();
    }

    public function test_sin_clave_configurada_el_esp32_queda_cerrado(): void
    {
        config(['dentissa.iot.clave_dispositivo' => '']);

        $this->getJson('/api/iot/comandos', ['X-Dispositivo-Clave' => ''])->assertStatus(503);
        $this->postJson('/api/iot/estado', [], ['X-Dispositivo-Clave' => 'cualquiera'])->assertStatus(503);
    }

    public function test_el_login_limita_los_intentos(): void
    {
        User::factory()->create(['email' => 'doctora@example.test']);

        foreach (range(1, 5) as $intento) {
            $this->postJson('/api/app/login', ['email' => 'doctora@example.test', 'password' => 'mala'])->assertUnauthorized();
        }
        $this->postJson('/api/app/login', ['email' => 'doctora@example.test', 'password' => 'password'])->assertStatus(429);
    }
}

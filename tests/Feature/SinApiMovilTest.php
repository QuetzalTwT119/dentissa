<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Dentissa es solo web (decisión del 24/11/2026). La API de la app móvil y del
 * ESP32 (PR #80) se quitó: no pedía ninguna clave y /api/iot/puerta/abrir dejaba
 * abrir la puerta del consultorio a cualquiera. Si se retoma la app, debe
 * volver con autenticación (token por usuario y clave por dispositivo).
 */
class SinApiMovilTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_existe_ninguna_ruta_api(): void
    {
        $rutasApi = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta) => str_starts_with($ruta->uri(), 'api/'))
            ->map(fn ($ruta) => implode('|', $ruta->methods()).' '.$ruta->uri())
            ->values()
            ->all();

        $this->assertSame([], $rutasApi);
    }

    public function test_la_puerta_y_el_login_de_la_app_ya_no_responden(): void
    {
        $doctora = User::factory()->create();

        $this->postJson('/api/iot/puerta/abrir')->assertNotFound();
        $this->postJson('/api/iot/actuadores', ['luces' => ['modo' => 'ON']])->assertNotFound();
        $this->getJson('/api/iot/estado')->assertNotFound();
        $this->postJson('/api/app/login', ['email' => $doctora->email, 'password' => 'password'])->assertNotFound();
    }
}

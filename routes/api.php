<?php

use App\Http\Controllers\Api\AppAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
| API de la app móvil del personal y del ESP32 de la sala de espera.
| 26/11/2026: protegida (antes cualquiera podía abrir la puerta):
| - app: «Authorization: Bearer <token>» del login (middleware app.token);
| - ESP32: «X-Dispositivo-Clave» = DENTISSA_IOT_CLAVE (middleware iot.dispositivo);
| - login: máximo 5 intentos por minuto (throttle:app-login).
*/

Route::post('/app/login', [AppAuthController::class, 'login'])->middleware('throttle:app-login');
Route::post('/app/logout', [AppAuthController::class, 'logout'])->middleware('app.token');

// Rutas que usa el ESP32 (clave del dispositivo).
Route::prefix('iot')->middleware('iot.dispositivo')->group(function () {

    // 1. El ESP32 envía el estado físico de los 6 sensores IR, 4 ventiladores y luces
    Route::post('/estado', function (Request $request) {
        Cache::put('esp32_en_linea', true, now()->addSeconds(15));

        Cache::put('iot_asientos', $request->input('asientos', []), now()->addHours(12));

        if ($request->has('ventiladores')) {
            Cache::put('iot_ventiladores_estado', $request->input('ventiladores'), now()->addHours(12));
        }

        if ($request->has('luces')) {
            Cache::put('iot_luces', $request->input('luces'), now()->addHours(12));
        }

        return response()->json(['ok' => true]);
    });

    // 4. El ESP32 consulta órdenes de puerta, luces y ventiladores
    Route::get('/comandos', function () {
        $abrirPuerta = (bool) Cache::pull('iot_abrir_puerta', false);
        $ventiladores = Cache::get('iot_comandos_ventiladores', []);
        $luces = Cache::get('iot_luces', ['modo' => 'AUTO', 'encendido' => false]);

        return response()->json([
            'abrir_puerta' => $abrirPuerta,
            'luces' => $luces,
            'ventiladores' => $ventiladores,
        ]);
    });
});

// Rutas que usa la app (token del personal).
Route::prefix('iot')->middleware('app.token')->group(function () {

    // 2. La app Ionic consulta el estado actual del consultorio (100% desde Cache)
    Route::get('/estado', function () {
        $asientosCache = Cache::get('iot_asientos', []);
        $asientos = [];

        for ($i = 1; $i <= 6; $i++) {
            $remoto = collect($asientosCache)->firstWhere('numero', $i);
            $ocupado = (bool) ($remoto['ocupado'] ?? false);
            $asientos[] = [
                'numero' => $i,
                'ocupado' => $ocupado,
                'vecesUsado' => $ocupado ? 1 : 0,
            ];
        }

        return response()->json([
            'esp32_en_linea' => Cache::has('esp32_en_linea'),
            'asientos' => $asientos,
            'ventiladores' => Cache::get('iot_ventiladores_estado', []),
            'luces' => Cache::get('iot_luces', ['modo' => 'AUTO', 'encendido' => false]),
        ]);
    });

    // 3. Ionic envía control manual para LED o Ventiladores
    Route::post('/actuadores', function (Request $request) {
        $dispositivo = $request->input('dispositivo');
        $modo = $request->input('modo', 'AUTO');
        $encendido = (bool) $request->input('encendido', false);

        if ($dispositivo === 'LUCES') {
            Cache::put('iot_luces', ['modo' => $modo, 'encendido' => $encendido], now()->addHours(12));
        }

        if ($request->has('ventiladores')) {
            Cache::put('iot_comandos_ventiladores', $request->input('ventiladores'), now()->addHours(12));
        }

        return response()->json(['ok' => true]);
    });

    // 5. Ionic solicita abrir la puerta principal (Servo D27)
    Route::post('/puerta/abrir', function (Request $request) {
        // Queda registrado quién la abrió.
        Log::info('IoT: puerta abierta desde la app', ['usuario' => $request->user()->id]);
        Cache::put('iot_abrir_puerta', true, now()->addSeconds(15));
        Cache::put('iot_luces', ['modo' => 'MANUAL_ON', 'encendido' => true], now()->addHours(12));

        return response()->json(['ok' => true]);
    });

    // 6. Estadísticas en vacío mientras estamos en fase de pruebas de hardware
    Route::get('/estadisticas', function (Request $request) {
        return response()->json([
            'periodo' => $request->query('periodo', 'hoy'),
            'mes' => (int) $request->query('mes', now()->month),
            'anio' => (int) $request->query('anio', now()->year),
            'asientos' => [],
        ]);
    });
});

<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::post('/app/login', [\App\Http\Controllers\Api\AppAuthController::class, 'login']);

Route::prefix('iot')->group(function () {

    // 1. El ESP32 envía el estado físico de los 6 sensores IR y 4 ventiladores
    Route::post('/estado', function (Request $request) {
        Cache::put('esp32_en_linea', true, now()->addSeconds(10));

        $asientosNuevos = $request->input('asientos', []);
        $asientosPrevios = Cache::get('iot_asientos', []);

        foreach ($asientosNuevos as $item) {
            $num = (int) ($item['numero'] ?? 0);
            $ocupadoAhora = (bool) ($item['ocupado'] ?? false);
            if ($num < 1 || $num > 6) continue;

            $previo = collect($asientosPrevios)->firstWhere('numero', $num);
            $ocupadoAntes = (bool) ($previo['ocupado'] ?? false);

            // Al sentarse alguien: registrar inicio en caché
            if (!$ocupadoAntes && $ocupadoAhora) {
                Cache::put("asiento_inicio_{$num}", now()->toDateTimeString(), now()->addHours(12));
            }

            // Al levantarse alguien: guardar sesión completa en MySQL/SQLite
            if ($ocupadoAntes && !$ocupadoAhora) {
                $inicioStr = Cache::pull("asiento_inicio_{$num}");
                $inicio = $inicioStr ? Carbon::parse($inicioStr) : now()->subSeconds(5);
                $duracion = max(1, (int) $inicio->diffInSeconds(now()));

                DB::table('historial_asientos')->insert([
                    'asiento_numero'    => $num,
                    'inicio_ocupacion'  => $inicio,
                    'fin_ocupacion'     => now(),
                    'duracion_segundos' => $duracion,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        }

        Cache::put('iot_asientos', $asientosNuevos, now()->addHours(12));

        if ($request->has('ventiladores')) {
            Cache::put('iot_ventiladores_estado', $request->input('ventiladores'), now()->addHours(12));
        }

        return response()->json(['ok' => true]);
    });

    // 2. La app Ionic consulta el estado actual del consultorio cada 1s
    Route::get('/estado', function () {
        $usosHoy = DB::table('historial_asientos')
            ->select('asiento_numero', DB::raw('COUNT(*) as total'))
            ->whereDate('created_at', Carbon::today())
            ->groupBy('asiento_numero')
            ->pluck('total', 'asiento_numero');

        $asientosCache = Cache::get('iot_asientos', []);
        $asientos = [];
        for ($i = 1; $i <= 6; $i++) {
            $remoto = collect($asientosCache)->firstWhere('numero', $i);
            $ocupado = (bool) ($remoto['ocupado'] ?? false);
            $asientos[] = [
                'numero'     => $i,
                'ocupado'    => $ocupado,
                'vecesUsado' => (int) ($usosHoy[$i] ?? 0) + ($ocupado ? 1 : 0),
            ];
        }

        return response()->json([
            'esp32_en_linea' => Cache::has('esp32_en_linea'),
            'asientos'       => $asientos,
            'ventiladores'   => Cache::get('iot_ventiladores_estado', []),
            'luces'          => Cache::get('iot_luces', ['modo' => 'AUTO', 'encendido' => false]),
        ]);
    });

    // 3. Ionic envía anulación manual temporal (5s) para LED o Ventiladores
    Route::post('/actuadores', function (Request $request) {
        $dispositivo = $request->input('dispositivo');
        $modo        = $request->input('modo', 'AUTO');
        $encendido   = (bool) $request->input('encendido', false);

        if ($dispositivo === 'LUCES') {
            Cache::put('iot_luces', ['modo' => $modo, 'encendido' => $encendido], now()->addHours(12));
        }

        if ($request->has('ventiladores')) {
            Cache::put('iot_comandos_ventiladores', $request->input('ventiladores'), now()->addHours(12));
        }

        return response()->json(['ok' => true]);
    });

    // 4. El ESP32 consulta si hay orden de abrir puerta o modo manual en ventiladores
    Route::get('/comandos', function () {
        $abrirPuerta = (bool) Cache::pull('iot_abrir_puerta', false);
        $ventiladores = Cache::get('iot_comandos_ventiladores', []);

        return response()->json([
            'abrir_puerta' => $abrirPuerta,
            'ventiladores' => $ventiladores,
        ]);
    });

    // 5. Ionic solicita abrir la puerta principal (Servo D27) tras escanear QR
    Route::post('/puerta/abrir', function () {
        Cache::put('iot_abrir_puerta', true, now()->addSeconds(15));
        return response()->json(['ok' => true]);
    });

    // 6. Estadísticas históricas filtradas por Hoy, Semana, Mes (mes/año) o Año
    Route::get('/estadisticas', function (Request $request) {
        $periodo = $request->query('periodo', 'hoy');
        $mes     = (int) $request->query('mes', Carbon::now()->month);
        $anio    = (int) $request->query('anio', Carbon::now()->year);

        $query = DB::table('historial_asientos')
            ->select(
                'asiento_numero as numero',
                DB::raw('COUNT(*) as veces_usado'),
                DB::raw('COALESCE(SUM(duracion_segundos), 0) as tiempo_total_segundos')
            );

        if ($periodo === 'mes') {
            $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
            $fin    = Carbon::create($anio, $mes, 1)->endOfMonth();
            $query->whereBetween('created_at', [$inicio, $fin]);
        } elseif ($periodo === 'anio') {
            $inicio = Carbon::create($anio, 1, 1)->startOfYear();
            $fin    = Carbon::create($anio, 12, 31)->endOfYear();
            $query->whereBetween('created_at', [$inicio, $fin]);
        } elseif ($periodo === 'semana') {
            $query->where('created_at', '>=', Carbon::now()->startOfWeek());
        } else {
            $query->whereDate('created_at', Carbon::today());
        }

        return response()->json([
            'periodo'  => $periodo,
            'mes'      => $mes,
            'anio'     => $anio,
            'asientos' => $query->groupBy('asiento_numero')->get(),
        ]);
    });
});
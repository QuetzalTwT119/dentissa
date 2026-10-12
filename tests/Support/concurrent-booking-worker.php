<?php

use App\Models\Cita;
use App\Models\Tratamiento;
use App\Services\DisponibilidadService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

[$script, $patientId, $serviceId, $treatmentId, $dateTime, $barrier, $workerId, $testDatabase] = $argv;
$basePath = dirname(__DIR__, 2);

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv("DB_DATABASE={$testDatabase}");

require $basePath.'/vendor/autoload.php';
$app = require $basePath.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
    file_put_contents($barrier.DIRECTORY_SEPARATOR."ready-{$workerId}", 'ready');
    $deadline = microtime(true) + 15;
    while (! is_file($barrier.DIRECTORY_SEPARATOR.'release') && microtime(true) < $deadline) {
        usleep(20_000);
    }

    if (! is_file($barrier.DIRECTORY_SEPARATOR.'release')) {
        throw new RuntimeException('Tiempo agotado esperando la barrera de concurrencia.');
    }

    $accepted = DB::transaction(function () use ($dateTime, $patientId, $serviceId, $treatmentId) {
        $tratamiento = Tratamiento::findOrFail($treatmentId);
        $fechaHora = CarbonImmutable::parse($dateTime);
        $disponibilidad = app(DisponibilidadService::class);

        if (! $disponibilidad->estaDisponible($fechaHora, $tratamiento)) {
            return false;
        }

        Cita::create([
            'id_paciente' => $patientId,
            'id_servicio' => $serviceId,
            'id_tratamiento' => $treatmentId,
            'fecha_hora' => $fechaHora,
            'duracion_estimada' => $tratamiento->duracion_estimada,
            'motivo' => 'Prueba automatizada de concurrencia',
        ]);

        // Mantiene la primera transacción abierta para que la otra compita por el mutex.
        usleep(300_000);

        return true;
    });

    echo json_encode(['accepted' => $accepted], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage());
    exit(1);
}

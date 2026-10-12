<?php

namespace Tests\Feature;

use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrenciaCitasMySQLTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        if ((getenv('DENTISSA_TEST_DB_CONNECTION') ?: 'sqlite') !== 'mysql') {
            $this->markTestSkipped('Esta prueba requiere MySQL; SQLite no simula los bloqueos FOR UPDATE.');
        }

        parent::setUp();
    }

    public function test_solo_una_reserva_concurrente_ocupa_el_ultimo_cupo(): void
    {
        $fechaHora = Carbon::tomorrow()->setTime(10, 0);
        while ($fechaHora->isSunday()) {
            $fechaHora->addDay();
        }

        DB::table('horarios_atencion')
            ->where('dia_semana', $fechaHora->dayOfWeek)
            ->update([
                'abierto' => true,
                'hora_apertura' => 8,
                'hora_cierre' => 18,
                'capacidad_simultanea' => 1,
            ]);

        $servicio = Servicio::create(['nombre' => 'Servicio concurrencia QA', 'activo' => true]);
        $tratamiento = Tratamiento::create([
            'nombre' => 'Tratamiento concurrencia QA',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $servicio->tratamientos()->attach($tratamiento);

        $pacientes = collect([1, 2])->map(function (int $numero) {
            $usuario = User::factory()->create();

            return Paciente::create([
                'id_usuario' => $usuario->id,
                'nombre' => "Paciente QA {$numero}",
                'apellido' => 'Concurrencia',
                'telefono' => '5555555555',
                'email' => "concurrencia-{$numero}-".uniqid().'@example.test',
            ]);
        });

        $barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dentissa-concurrency-'.bin2hex(random_bytes(8));
        mkdir($barrier);

        try {
            $mysqlConfig = config('database.connections.mysql');
            $workerEnvironment = array_merge(getenv(), [
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'mysql',
                'DB_DATABASE' => getenv('DENTISSA_TEST_DB_DATABASE') ?: 'dentissa_test',
                'DB_HOST' => (string) $mysqlConfig['host'],
                'DB_PORT' => (string) $mysqlConfig['port'],
                'DB_USERNAME' => (string) $mysqlConfig['username'],
                'DB_PASSWORD' => (string) $mysqlConfig['password'],
                'DB_SOCKET' => (string) ($mysqlConfig['unix_socket'] ?? ''),
                'DB_URL' => '',
            ]);

            $processes = $pacientes->map(fn (Paciente $paciente, int $index) => new Process([
                PHP_BINARY,
                base_path('tests/Support/concurrent-booking-worker.php'),
                (string) $paciente->id,
                (string) $servicio->id,
                (string) $tratamiento->id,
                $fechaHora->format('Y-m-d H:i:s'),
                $barrier,
                (string) ($index + 1),
                $workerEnvironment['DB_DATABASE'],
            ], base_path(), $workerEnvironment, timeout: 20));

            foreach ($processes as $process) {
                $process->start();
            }

            $deadline = microtime(true) + 10;
            while (count(glob($barrier.DIRECTORY_SEPARATOR.'ready-*') ?: []) < 2 && microtime(true) < $deadline) {
                usleep(20_000);
            }

            $this->assertCount(2, glob($barrier.DIRECTORY_SEPARATOR.'ready-*') ?: [], 'Ambos procesos deben estar listos antes de abrir la barrera.');
            touch($barrier.DIRECTORY_SEPARATOR.'release');

            $accepted = 0;
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $result = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);
                $accepted += (int) $result['accepted'];
            }

            $this->assertSame(1, $accepted, 'Exactamente un proceso debe reservar el único cupo.');
            $this->assertSame(1, DB::table('citas')->whereDate('fecha_hora', $fechaHora->toDateString())->count());
        } finally {
            foreach ($processes ?? [] as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }

            foreach (glob($barrier.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            rmdir($barrier);
        }
    }
}

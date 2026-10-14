<?php

namespace Tests\Unit;

use App\Exceptions\AuditoriaNoDisponible;
use App\Models\Paciente;
use App\Services\AuditoriaService;
use LogicException;
use Tests\TestCase;

class AuditoriaServiceTest extends TestCase
{
    public function test_rechaza_registrar_fuera_de_una_transaccion(): void
    {
        $this->assertErrorDeContrato(
            new Paciente(),
            'AuditoriaService::registrar requiere una transacción activa.',
        );
    }

    public function test_rechaza_una_conexion_distinta_a_la_de_la_bitacora(): void
    {
        config([
            'database.connections.auditoria_qa' => config(
                'database.connections.'.config('database.default')
            ),
        ]);

        $paciente = (new Paciente())->setConnection('auditoria_qa');

        $this->assertErrorDeContrato(
            $paciente,
            'El modelo y la auditoría deben usar la misma conexión de base de datos.',
        );
    }

    private function assertErrorDeContrato(Paciente $paciente, string $mensaje): void
    {
        try {
            app(AuditoriaService::class)->registrar('paciente.prueba', $paciente);
            $this->fail('La auditoría debe rechazar la configuración inválida.');
        } catch (AuditoriaNoDisponible $exception) {
            $this->assertInstanceOf(LogicException::class, $exception->getPrevious());
            $this->assertSame($mensaje, $exception->getPrevious()->getMessage());
        }
    }
}

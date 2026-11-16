<?php

namespace App\Services\Respaldo;

use Carbon\CarbonInterface;

/**
 * Lugar fuera del servidor donde se guardan los respaldos cifrados.
 * Hoy es Google Drive; las pruebas usan uno en memoria y, si la clínica
 * paga otro almacenamiento, basta con otra implementación.
 */
interface DestinoRespaldo
{
    /** Sube el archivo y devuelve su identificador en el destino. */
    public function subir(string $rutaLocal, string $nombre): string;

    /**
     * Respaldos guardados, del más reciente al más antiguo.
     *
     * @return list<array{id: string, nombre: string, creado: CarbonInterface, bytes: int}>
     */
    public function listar(): array;

    public function descargar(string $id, string $rutaLocal): void;

    public function eliminar(string $id): void;
}

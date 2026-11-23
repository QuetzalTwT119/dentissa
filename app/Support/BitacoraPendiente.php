<?php

namespace App\Support;

use App\Models\LogAuditoria;

/**
 * Evita registrar dos veces el mismo cambio en Control de actividades
 * (22/11/2026). El observador de modelos dejaba un evento genérico
 * ("actualizado", sin detalle) además del evento con detalle que escribe
 * AuditoriaService ("Oferta actualizada", con antes y después): casi la mitad
 * de la bitácora estaba duplicada.
 *
 * Ahora el observador deja su evento PENDIENTE y se guarda al terminar la
 * petición solo si ese registro (tabla + id) no tuvo ya un evento con detalle.
 * Vive una sola petición (singleton "scoped").
 */
class BitacoraPendiente
{
    /** @var array<string, true> tabla#id con evento detallado en esta petición */
    private array $detallados = [];

    /** @var list<array<string, mixed>> eventos genéricos por escribir */
    private array $pendientes = [];

    public function marcarDetallado(string $tabla, mixed $id): void
    {
        $this->detallados[$tabla.'#'.$id] = true;
    }

    /** @param  array<string, mixed>  $evento */
    public function agregar(array $evento): void
    {
        $this->pendientes[] = $evento;
    }

    public function escribir(): void
    {
        foreach ($this->pendientes as $evento) {
            if (! isset($this->detallados[$evento['tabla'].'#'.$evento['registro_id']])) {
                LogAuditoria::query()->create($evento);
            }
        }

        $this->pendientes = [];
        $this->detallados = [];
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Regla del 20/11/2026: un tratamiento sin precio no se publica. Desde ahora
 * "activo" siempre implica "tiene precio" (el formulario lo exige y el
 * interruptor no activa sin precio); esta migración pone en regla los datos
 * existentes. Para volver a publicarlos basta con capturar su precio y
 * activarlos (o correr ServiciosTratamientosSeeder, que trae los precios).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tratamientos')
            ->where('activo', true)
            ->where(fn ($q) => $q->whereNull('precio_referencia')->orWhere('precio_referencia', '<=', 0))
            ->update(['activo' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // No se reactiva nada a ciegas: no se sabe cuáles estaban activos antes.
    }
};

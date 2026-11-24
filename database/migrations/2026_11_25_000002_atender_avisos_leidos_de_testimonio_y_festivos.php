<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * QA de notificaciones (25/11/2026): «testimonio_nuevo» y «festivos_con_citas»
 * ahora van en «Por atender». Los avisos viejos de esos tipos que la doctora ya
 * leyó se dan por atendidos, para que la campana no se llene de golpe con cosas
 * que ya revisó. Los no leídos se quedan por atender.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('notificaciones_sistema')
            ->whereIn('evento', ['testimonio_nuevo', 'festivos_con_citas'])
            ->whereNull('atendida_en')
            ->whereNotNull('leida_en')
            ->update(['atendida_en' => DB::raw('leida_en')]);
    }

    public function down(): void
    {
        // Sin vuelta atrás necesaria: «atendida_en» solo saca el aviso de «Por atender».
    }
};

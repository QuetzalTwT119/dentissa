<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 21/11/2026: la cita guarda la oferta con la que se agendó. Así la doctora ve
 * en la agenda que el paciente viene por una promoción (y la aplica al cobrar)
 * y los "lugares" de la oferta se cuentan con citas reales. Si la oferta se
 * elimina, la cita se conserva sin ella.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->foreignId('id_oferta')->nullable()->after('id_tratamiento')
                ->constrained('ofertas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_oferta');
        });
    }
};

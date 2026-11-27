<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 27/11/2026: el usuario pidió no mostrar la clave COFEPRIS en la portada. La
 * columna ya existía en producción (migración 2026_11_27_000001), así que se
 * quita con una migración nueva en lugar de editar la anterior.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('datos_consultorio', 'cofepris')) {
            Schema::table('datos_consultorio', function (Blueprint $table) {
                $table->dropColumn('cofepris');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('datos_consultorio', 'cofepris')) {
            Schema::table('datos_consultorio', function (Blueprint $table) {
                $table->string('cofepris', 60)->nullable()->after('vision');
            });
        }
    }
};

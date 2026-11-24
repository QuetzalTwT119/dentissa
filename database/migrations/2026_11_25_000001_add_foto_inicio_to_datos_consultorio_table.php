<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto grande del inicio de la portada (25/11/2026). Se sube en Contenido →
 * Galería; vacía = la foto de fábrica. Ruta dentro del disco "public".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->string('foto_inicio', 255)->nullable()->after('vision');
        });
    }

    public function down(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->dropColumn('foto_inicio');
        });
    }
};

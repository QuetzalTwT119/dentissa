<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 24/11/2026: Misión y Visión de la portada (bloque "La clínica"), editables en
 * Configuración → Datos del consultorio. Vacías, la portada usa el texto de
 * config/dentissa.php. Sin certificados: los decide la doctora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->string('mision', 400)->nullable()->after('direccion');
            $table->string('vision', 400)->nullable()->after('mision');
        });
    }

    public function down(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->dropColumn(['mision', 'vision']);
        });
    }
};

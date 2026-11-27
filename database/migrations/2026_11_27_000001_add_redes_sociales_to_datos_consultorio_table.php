<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 27/11/2026: redes sociales del pie de la portada (idea tomada de sitios de
 * clínicas de referencia). Opcionales: vacías, el pie no muestra ese ícono.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->string('instagram', 200)->nullable()->after('vision');
            $table->string('facebook', 200)->nullable()->after('instagram');
            $table->string('tiktok', 200)->nullable()->after('facebook');
        });
    }

    public function down(): void
    {
        Schema::table('datos_consultorio', function (Blueprint $table) {
            $table->dropColumn(['instagram', 'facebook', 'tiktok']);
        });
    }
};
